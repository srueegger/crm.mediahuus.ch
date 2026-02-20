<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\InsuranceAssessment;
use App\Repositories\DocumentRepository;
use App\Repositories\InsuranceAssessmentRepository;
use App\Repositories\BranchRepository;
use App\Services\PdfService;
use App\Services\DamageTypeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class InsuranceController extends BaseController
{
    private DocumentRepository $documentRepository;
    private InsuranceAssessmentRepository $insuranceRepository;
    private BranchRepository $branchRepository;
    private PdfService $pdfService;
    private LoggerInterface $logger;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        InsuranceAssessmentRepository $insuranceRepository,
        BranchRepository $branchRepository,
        PdfService $pdfService,
        LoggerInterface $logger
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->insuranceRepository = $insuranceRepository;
        $this->branchRepository = $branchRepository;
        $this->pdfService = $pdfService;
        $this->logger = $logger;
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');

        try {
            $branches = $this->branchRepository->findAll();

            return $this->render($response, 'insurance/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                'damageTypes' => DamageTypeService::getDamageTypes(),
                'damageTemplates' => DamageTypeService::getDamageTemplates(),
                'resultOptions' => InsuranceAssessment::getResultOptions(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load data for insurance creation', ['error' => $e->getMessage()]);

            return $this->render($response, 'insurance/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => [],
                'error' => 'Fehler beim Laden der Daten'
            ]);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $data = $request->getParsedBody();

        $errors = $this->validateInput($data);

        if (!empty($errors)) {
            try {
                $branches = $this->branchRepository->findAll();
                return $this->render($response, 'insurance/create.html.twig', [
                    'user' => $currentUser->toArray(),
                    'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                    'damageTypes' => DamageTypeService::getDamageTypes(),
                    'damageTemplates' => DamageTypeService::getDamageTemplates(),
                    'resultOptions' => InsuranceAssessment::getResultOptions(),
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load branches after validation error', ['error' => $e->getMessage()]);
                return $response->withHeader('Location', '/?error=form')->withStatus(302);
            }
        }

        try {
            $docNumber = $this->documentRepository->generateDocNumber(Document::TYPE_INSURANCE);

            $document = new Document(
                docType: Document::TYPE_INSURANCE,
                docNumber: $docNumber,
                branchId: (int) $data['branch_id'],
                userId: $currentUser->getId(),
                customerName: trim($data['customer_name']),
                customerPhone: trim($data['customer_phone'] ?? '') ?: null,
                customerEmail: trim($data['customer_email'] ?? '') ?: null
            );

            $documentId = $this->documentRepository->create($document);

            $deviceValue = trim($data['device_value_chf'] ?? '');
            $repairCost = trim($data['repair_cost_chf'] ?? '');

            $assessment = new InsuranceAssessment(
                documentId: $documentId,
                damageType: $data['damage_type'],
                deviceName: trim($data['device_name']),
                serialNumber: trim($data['serial_number']),
                damageDescription: trim($data['damage_description'] ?? ''),
                assessmentResult: $data['assessment_result'],
                deviceValueChf: $deviceValue !== '' ? (float) $deviceValue : null,
                repairCostChf: $repairCost !== '' ? (float) $repairCost : null
            );

            $assessmentId = $this->insuranceRepository->create($assessment);

            $this->logger->info('Insurance assessment created', [
                'document_id' => $documentId,
                'assessment_id' => $assessmentId,
                'doc_number' => $docNumber,
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/insurance/' . $documentId)->withStatus(302);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create insurance assessment', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/?error=create')->withStatus(302);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];

        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_INSURANCE) {
                return $response->withHeader('Location', '/?error=notfound')->withStatus(302);
            }

            $assessment = $this->insuranceRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());

            if (!$assessment || !$branch) {
                return $response->withHeader('Location', '/?error=incomplete')->withStatus(302);
            }

            return $this->render($response, 'insurance/show.html.twig', [
                'user' => $currentUser->toArray(),
                'document' => $document->toArray(),
                'assessment' => $assessment->toArray(),
                'branch' => $branch->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch insurance assessment details', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return $response->withHeader('Location', '/?error=fetch')->withStatus(302);
        }
    }

    public function generatePdf(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];

        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_INSURANCE) {
                return $response->withHeader('Location', '/?error=notfound')->withStatus(302);
            }

            $assessment = $this->insuranceRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());

            if (!$assessment || !$branch) {
                return $response->withHeader('Location', '/?error=incomplete')->withStatus(302);
            }

            $pdfContent = $this->pdfService->generateInsurancePdf($document, $assessment, $branch);
            $filename = $this->pdfService->getInsurancePdfFilename($document);

            $response = $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) strlen($pdfContent));

            $response->getBody()->write($pdfContent);

            $this->logger->info('Insurance PDF downloaded', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'filename' => $filename
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Insurance PDF generation failed', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage()
            ]);

            return $response->withHeader('Location', '/?error=pdf_generation')->withStatus(302);
        }
    }

    private function validateInput(array $data): array
    {
        $errors = [];

        if (empty(trim($data['customer_name'] ?? ''))) {
            $errors['customer_name'] = 'Kundenname ist erforderlich';
        }

        $phone = trim($data['customer_phone'] ?? '');
        if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
            $errors['customer_phone'] = 'Ungültiges Telefonnummer-Format';
        }

        $email = trim($data['customer_email'] ?? '');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'Ungültige E-Mail-Adresse';
        }

        if (empty($data['damage_type'] ?? '')) {
            $errors['damage_type'] = 'Schadenstyp ist erforderlich';
        } elseif (!DamageTypeService::isValidDamageType($data['damage_type'])) {
            $errors['damage_type'] = 'Ungültiger Schadenstyp';
        }

        if (empty(trim($data['device_name'] ?? ''))) {
            $errors['device_name'] = 'Gerätename ist erforderlich';
        }

        if (empty(trim($data['serial_number'] ?? ''))) {
            $errors['serial_number'] = 'Seriennummer ist erforderlich';
        }

        if (empty($data['assessment_result'] ?? '')) {
            $errors['assessment_result'] = 'Bewertungsergebnis ist erforderlich';
        } elseif (!InsuranceAssessment::isValidResult($data['assessment_result'])) {
            $errors['assessment_result'] = 'Ungültiges Bewertungsergebnis';
        }

        $deviceValue = $data['device_value_chf'] ?? '';
        if ($deviceValue !== '' && (!is_numeric($deviceValue) || (float)$deviceValue < 0)) {
            $errors['device_value_chf'] = 'Zeitwert muss eine positive Zahl sein';
        }

        $repairCost = $data['repair_cost_chf'] ?? '';
        if ($repairCost !== '' && (!is_numeric($repairCost) || (float)$repairCost < 0)) {
            $errors['repair_cost_chf'] = 'Reparaturkosten müssen eine positive Zahl sein';
        }

        if (empty($data['branch_id'] ?? '')) {
            $errors['branch_id'] = 'Filiale ist erforderlich';
        }

        return $errors;
    }
}
