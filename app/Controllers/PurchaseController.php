<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Purchase;
use App\Repositories\DocumentRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\BranchRepository;
use App\Services\PdfService;
use App\Services\FileUploadService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class PurchaseController extends BaseController
{
    private DocumentRepository $documentRepository;
    private PurchaseRepository $purchaseRepository;
    private BranchRepository $branchRepository;
    private PdfService $pdfService;
    private FileUploadService $fileUploadService;
    private LoggerInterface $logger;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        PurchaseRepository $purchaseRepository,
        BranchRepository $branchRepository,
        PdfService $pdfService,
        FileUploadService $fileUploadService,
        LoggerInterface $logger
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->purchaseRepository = $purchaseRepository;
        $this->branchRepository = $branchRepository;
        $this->pdfService = $pdfService;
        $this->fileUploadService = $fileUploadService;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');

        try {
            // Get all purchase documents
            $documents = $this->documentRepository->findAll(['doc_type' => Document::TYPE_PURCHASE]);

            // Fetch associated purchases and branches for each document
            $purchases = [];
            foreach ($documents as $document) {
                $purchase = $this->purchaseRepository->findByDocumentId($document->getId());
                $branch = $this->branchRepository->findById($document->getBranchId());

                if ($purchase && $branch) {
                    $purchases[] = [
                        'document' => $document->toArray(),
                        'purchase' => $purchase->toArray(),
                        'branch' => $branch->toArray(),
                    ];
                }
            }

            return $this->render($response, 'purchases/index.html.twig', [
                'user' => $currentUser->toArray(),
                'purchases' => $purchases,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch purchases', ['error' => $e->getMessage()]);

            return $this->render($response, 'purchases/index.html.twig', [
                'user' => $currentUser->toArray(),
                'purchases' => [],
                'error' => 'Fehler beim Laden der Ankäufe'
            ]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');

        try {
            $branches = $this->branchRepository->findAll();

            return $this->render($response, 'purchases/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                'deviceTypes' => $this->getDeviceTypes(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load branches for purchase creation', ['error' => $e->getMessage()]);

            return $this->render($response, 'purchases/create.html.twig', [
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
        $errors = $this->validatePurchaseInput($data);

        if (!empty($errors)) {
            try {
                $branches = $this->branchRepository->findAll();
                return $this->render($response, 'purchases/create.html.twig', [
                    'user' => $currentUser->toArray(),
                    'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                    'deviceTypes' => $this->getDeviceTypes(),
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load branches after validation error', ['error' => $e->getMessage()]);
                return $response->withHeader('Location', '/purchases?error=form')->withStatus(302);
            }
        }

        try {
            // Handle image uploads (base64 from camera)
            $idDocumentFront = null;
            $idDocumentBack = null;

            if (!empty($data['id_document_front'])) {
                $idDocumentFront = $this->fileUploadService->handleBase64Upload(
                    $data['id_document_front'],
                    'id_front'
                );
            }

            if (!empty($data['id_document_back'])) {
                $idDocumentBack = $this->fileUploadService->handleBase64Upload(
                    $data['id_document_back'],
                    'id_back'
                );
            }

            if (!$idDocumentFront) {
                throw new \RuntimeException('ID document front is required');
            }

            // Generate document number
            $docNumber = $this->documentRepository->generateDocNumber(Document::TYPE_PURCHASE);

            // Create document
            $document = new Document(
                docType: Document::TYPE_PURCHASE,
                docNumber: $docNumber,
                branchId: (int) $data['branch_id'],
                userId: $currentUser->getId(),
                customerName: trim($data['customer_name']),
                customerPhone: trim($data['customer_phone']) ?: null,
                customerEmail: trim($data['customer_email']) ?: null
            );

            $documentId = $this->documentRepository->create($document);

            // Create purchase details
            $purchase = new Purchase(
                documentId: $documentId,
                sellerStreet: trim($data['seller_street']),
                sellerZip: trim($data['seller_zip']),
                sellerCity: trim($data['seller_city']),
                deviceType: $data['device_type'],
                deviceBrand: trim($data['device_brand']),
                deviceModel: trim($data['device_model']),
                purchasePriceChf: (float) $data['purchase_price_chf'],
                idDocumentFront: $idDocumentFront,
                imei: !empty($data['imei']) ? trim($data['imei']) : null,
                serialNumber: !empty($data['serial_number']) ? trim($data['serial_number']) : null,
                deviceCondition: !empty($data['device_condition']) ? trim($data['device_condition']) : null,
                accessories: !empty($data['accessories']) ? trim($data['accessories']) : null,
                idDocumentBack: $idDocumentBack
            );

            $purchaseId = $this->purchaseRepository->create($purchase);

            $this->logger->info('Purchase created', [
                'document_id' => $documentId,
                'purchase_id' => $purchaseId,
                'doc_number' => $docNumber,
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/purchases/' . $documentId)->withStatus(302);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create purchase', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId()
            ]);

            // Clean up uploaded files if transaction failed
            if (isset($idDocumentFront)) {
                $this->fileUploadService->deleteFile($idDocumentFront);
            }
            if (isset($idDocumentBack)) {
                $this->fileUploadService->deleteFile($idDocumentBack);
            }

            return $response->withHeader('Location', '/purchases?error=create')->withStatus(302);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];

        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_PURCHASE) {
                return $response->withHeader('Location', '/purchases?error=notfound')->withStatus(302);
            }

            $purchase = $this->purchaseRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());

            if (!$purchase || !$branch) {
                return $response->withHeader('Location', '/purchases?error=incomplete')->withStatus(302);
            }

            return $this->render($response, 'purchases/show.html.twig', [
                'user' => $currentUser->toArray(),
                'document' => $document->toArray(),
                'purchase' => $purchase->toArray(),
                'branch' => $branch->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch purchase details', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return $response->withHeader('Location', '/purchases?error=fetch')->withStatus(302);
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
            if (!$document || $document->getDocType() !== Document::TYPE_PURCHASE) {
                return $response->withHeader('Location', '/purchases?error=notfound')->withStatus(302);
            }

            $purchase = $this->purchaseRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());

            if (!$purchase || !$branch) {
                return $response->withHeader('Location', '/purchases?error=incomplete')->withStatus(302);
            }

            // Generate PDF
            $pdfContent = $this->pdfService->generatePurchasePdf($document, $purchase, $branch);
            $filename = $this->pdfService->getPurchasePdfFilename($document);

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

            return $response->withHeader('Location', '/purchases?error=pdf_generation')->withStatus(302);
        }
    }

    private function validatePurchaseInput(array $data): array
    {
        $errors = [];

        // Customer name (Verkäufer)
        if (empty(trim($data['customer_name'] ?? ''))) {
            $errors['customer_name'] = 'Name des Verkäufers ist erforderlich';
        }

        // Customer phone
        $phone = trim($data['customer_phone'] ?? '');
        if (empty($phone)) {
            $errors['customer_phone'] = 'Telefonnummer ist erforderlich';
        } elseif (!preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
            $errors['customer_phone'] = 'Ungültiges Telefonnummer-Format';
        }

        // Seller address
        if (empty(trim($data['seller_street'] ?? ''))) {
            $errors['seller_street'] = 'Strasse ist erforderlich';
        }

        if (empty(trim($data['seller_zip'] ?? ''))) {
            $errors['seller_zip'] = 'PLZ ist erforderlich';
        }

        if (empty(trim($data['seller_city'] ?? ''))) {
            $errors['seller_city'] = 'Ort ist erforderlich';
        }

        // Device type
        if (empty($data['device_type'] ?? '')) {
            $errors['device_type'] = 'Gerätetyp ist erforderlich';
        }

        // Device brand
        if (empty(trim($data['device_brand'] ?? ''))) {
            $errors['device_brand'] = 'Marke ist erforderlich';
        }

        // Device model
        if (empty(trim($data['device_model'] ?? ''))) {
            $errors['device_model'] = 'Modell ist erforderlich';
        }

        // Purchase price
        $price = $data['purchase_price_chf'] ?? '';
        if (empty($price)) {
            $errors['purchase_price_chf'] = 'Ankaufspreis ist erforderlich';
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $errors['purchase_price_chf'] = 'Preis muss eine positive Zahl sein';
        }

        // ID documents (check if base64 data is present)
        if (empty($data['id_document_front'] ?? '')) {
            $errors['id_document_front'] = 'Ausweis Vorderseite ist erforderlich';
        }

        // Branch
        if (empty($data['branch_id'] ?? '')) {
            $errors['branch_id'] = 'Filiale ist erforderlich';
        }

        return $errors;
    }

    private function getDeviceTypes(): array
    {
        return [
            'smartphone' => 'Smartphone',
            'tablet' => 'Tablet',
            'watch' => 'Apple Watch',
            'other' => 'Sonstiges',
        ];
    }
}
