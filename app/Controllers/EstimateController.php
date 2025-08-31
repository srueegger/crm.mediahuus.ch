<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Estimate;
use App\Repositories\DocumentRepository;
use App\Repositories\EstimateRepository;
use App\Repositories\BranchRepository;
use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class EstimateController extends BaseController
{
    private DocumentRepository $documentRepository;
    private EstimateRepository $estimateRepository;
    private BranchRepository $branchRepository;
    private PdfService $pdfService;
    private LoggerInterface $logger;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        EstimateRepository $estimateRepository,
        BranchRepository $branchRepository,
        PdfService $pdfService,
        LoggerInterface $logger
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->estimateRepository = $estimateRepository;
        $this->branchRepository = $branchRepository;
        $this->pdfService = $pdfService;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        try {
            // Get all estimate documents
            $documents = $this->documentRepository->findAll(['doc_type' => Document::TYPE_ESTIMATE]);
            
            // Fetch associated estimates and branches for each document
            $estimates = [];
            foreach ($documents as $document) {
                $estimate = $this->estimateRepository->findByDocumentId($document->getId());
                $branch = $this->branchRepository->findById($document->getBranchId());
                
                if ($estimate && $branch) {
                    $estimates[] = [
                        'document' => $document->toArray(),
                        'estimate' => $estimate->toArray(),
                        'branch' => $branch->toArray(),
                    ];
                }
            }
            
            return $this->render($response, 'estimates/index.html.twig', [
                'user' => $currentUser->toArray(),
                'estimates' => $estimates,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch estimates', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'estimates/index.html.twig', [
                'user' => $currentUser->toArray(),
                'estimates' => [],
                'error' => 'Fehler beim Laden der Kostenvoranschläge'
            ]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        try {
            $branches = $this->branchRepository->findAll();
            
            return $this->render($response, 'estimates/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load branches for estimate creation', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'estimates/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => [],
                'error' => 'Fehler beim Laden der Filialen'
            ]);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $data = $request->getParsedBody();
        
        // Validate input
        $errors = $this->validateEstimateInput($data);
        
        if (!empty($errors)) {
            try {
                $branches = $this->branchRepository->findAll();
                return $this->render($response, 'estimates/create.html.twig', [
                    'user' => $currentUser->toArray(),
                    'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load branches after validation error', ['error' => $e->getMessage()]);
                return $response->withHeader('Location', '/estimates?error=form')->withStatus(302);
            }
        }

        try {
            // Generate document number
            $docNumber = $this->documentRepository->generateDocNumber(Document::TYPE_ESTIMATE);
            
            // Create document
            $document = new Document(
                docType: Document::TYPE_ESTIMATE,
                docNumber: $docNumber,
                branchId: (int) $data['branch_id'],
                userId: $currentUser->getId(),
                customerName: trim($data['customer_name']),
                customerPhone: trim($data['customer_phone']) ?: null,
                customerEmail: trim($data['customer_email']) ?: null
            );

            $documentId = $this->documentRepository->create($document);
            
            // Create estimate details
            $estimate = new Estimate(
                documentId: $documentId,
                deviceName: trim($data['device_name']),
                serialNumber: trim($data['serial_number']),
                issueText: trim($data['issue_text']),
                priceChf: (float) $data['price_chf']
            );

            $estimateId = $this->estimateRepository->create($estimate);
            
            $this->logger->info('Estimate created', [
                'document_id' => $documentId,
                'estimate_id' => $estimateId,
                'doc_number' => $docNumber,
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/estimates/' . $documentId)->withStatus(302);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create estimate', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId()
            ]);
            
            return $response->withHeader('Location', '/estimates?error=create')->withStatus(302);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];
        
        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_ESTIMATE) {
                return $response->withHeader('Location', '/estimates?error=notfound')->withStatus(302);
            }
            
            $estimate = $this->estimateRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            
            if (!$estimate || !$branch) {
                return $response->withHeader('Location', '/estimates?error=incomplete')->withStatus(302);
            }
            
            return $this->render($response, 'estimates/show.html.twig', [
                'user' => $currentUser->toArray(),
                'document' => $document->toArray(),
                'estimate' => $estimate->toArray(),
                'branch' => $branch->toArray(),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch estimate details', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return $response->withHeader('Location', '/estimates?error=fetch')->withStatus(302);
        }
    }

    public function generatePdf(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];
        
        try {
            // Fetch all required data
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_ESTIMATE) {
                return $response->withHeader('Location', '/estimates?error=notfound')->withStatus(302);
            }
            
            $estimate = $this->estimateRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            
            if (!$estimate || !$branch) {
                return $response->withHeader('Location', '/estimates?error=incomplete')->withStatus(302);
            }

            // Generate PDF
            $pdfContent = $this->pdfService->generateEstimatePdf($document, $estimate, $branch);
            $filename = $this->pdfService->getEstimatePdfFilename($document);

            // Set headers for PDF download
            $response = $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) strlen($pdfContent));

            $response->getBody()->write($pdfContent);

            $this->logger->info('PDF downloaded', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'filename' => $filename
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('PDF generation failed', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $response->withHeader('Location', '/estimates?error=pdf_generation')->withStatus(302);
        }
    }

    private function validateEstimateInput(array $data): array
    {
        $errors = [];
        
        // Customer name
        if (empty(trim($data['customer_name'] ?? ''))) {
            $errors['customer_name'] = 'Kundenname ist erforderlich';
        }
        
        // Customer phone (optional but validate format if provided)
        $phone = trim($data['customer_phone'] ?? '');
        if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
            $errors['customer_phone'] = 'Ungültiges Telefonnummer-Format';
        }
        
        // Customer email (optional but validate format if provided)
        $email = trim($data['customer_email'] ?? '');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'Ungültige E-Mail-Adresse';
        }
        
        // Device name
        if (empty(trim($data['device_name'] ?? ''))) {
            $errors['device_name'] = 'Gerätename ist erforderlich';
        }
        
        // Serial number
        if (empty(trim($data['serial_number'] ?? ''))) {
            $errors['serial_number'] = 'Seriennummer ist erforderlich';
        }
        
        // Issue text
        if (empty(trim($data['issue_text'] ?? ''))) {
            $errors['issue_text'] = 'Schadens-/Fehlerbeschreibung ist erforderlich';
        }
        
        // Price
        $price = $data['price_chf'] ?? '';
        if (empty($price)) {
            $errors['price_chf'] = 'Preis ist erforderlich';
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $errors['price_chf'] = 'Preis muss eine positive Zahl sein';
        }
        
        // Branch
        if (empty($data['branch_id'] ?? '')) {
            $errors['branch_id'] = 'Filiale ist erforderlich';
        }
        
        return $errors;
    }
}