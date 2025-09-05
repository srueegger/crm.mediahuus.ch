<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Repositories\DocumentRepository;
use App\Repositories\ReceiptRepository;
use App\Repositories\ReceiptItemRepository;
use App\Repositories\BranchRepository;
use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ReceiptController extends BaseController
{
    private DocumentRepository $documentRepository;
    private ReceiptRepository $receiptRepository;
    private ReceiptItemRepository $receiptItemRepository;
    private BranchRepository $branchRepository;
    private PdfService $pdfService;
    private LoggerInterface $logger;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        ReceiptRepository $receiptRepository,
        ReceiptItemRepository $receiptItemRepository,
        BranchRepository $branchRepository,
        PdfService $pdfService,
        LoggerInterface $logger
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->receiptRepository = $receiptRepository;
        $this->receiptItemRepository = $receiptItemRepository;
        $this->branchRepository = $branchRepository;
        $this->pdfService = $pdfService;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        try {
            // Get all receipt documents
            $documents = $this->documentRepository->findAll(['doc_type' => Document::TYPE_RECEIPT]);
            
            // Fetch associated receipts and branches for each document
            $receipts = [];
            foreach ($documents as $document) {
                $receipt = $this->receiptRepository->findByDocumentId($document->getId());
                $branch = $this->branchRepository->findById($document->getBranchId());
                
                if ($receipt && $branch) {
                    $receipts[] = [
                        'document' => $document->toArray(),
                        'receipt' => $receipt->toArray(),
                        'branch' => $branch->toArray(),
                    ];
                }
            }
            
            return $this->render($response, 'receipts/index.html.twig', [
                'user' => $currentUser->toArray(),
                'receipts' => $receipts,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch receipts', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'receipts/index.html.twig', [
                'user' => $currentUser->toArray(),
                'receipts' => [],
                'error' => 'Fehler beim Laden der Quittungen'
            ]);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        try {
            $branches = $this->branchRepository->findAll();
            
            return $this->render($response, 'receipts/create.html.twig', [
                'user' => $currentUser->toArray(),
                'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load branches for receipt creation', ['error' => $e->getMessage()]);
            
            return $this->render($response, 'receipts/create.html.twig', [
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
        
        // Debug logging
        $this->logger->info('Receipt creation attempt', [
            'user_id' => $currentUser->getId(),
            'data' => $data
        ]);
        
        // Validate input
        $errors = $this->validateReceiptInput($data);
        
        if (!empty($errors)) {
            $this->logger->error('Receipt validation failed', ['errors' => $errors]);
        } else {
            $this->logger->info('Receipt validation passed');
        }
        
        if (!empty($errors)) {
            try {
                $branches = $this->branchRepository->findAll();
                return $this->render($response, 'receipts/create.html.twig', [
                    'user' => $currentUser->toArray(),
                    'branches' => array_map(fn($branch) => $branch->toArray(), $branches),
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load branches after validation error', ['error' => $e->getMessage()]);
                return $response->withHeader('Location', '/receipts?error=form')->withStatus(302);
            }
        }

        try {
            // Generate document number
            $docNumber = $this->documentRepository->generateDocNumber(Document::TYPE_RECEIPT);
            
            // Create document
            $document = new Document(
                docType: Document::TYPE_RECEIPT,
                docNumber: $docNumber,
                branchId: (int) $data['branch_id'],
                userId: $currentUser->getId(),
                customerName: 'Barkauf', // Default name for cash purchases
                customerPhone: null,
                customerEmail: null
            );

            $documentId = $this->documentRepository->create($document);
            
            // Parse and calculate receipt items
            $items = $this->parseReceiptItems($data['items'] ?? []);
            $totalAmount = array_sum(array_map(fn($item) => $item['line_total_chf'], $items));
            
            // Create receipt
            $receipt = new Receipt(
                documentId: $documentId,
                totalAmountChf: $totalAmount
            );

            $receiptId = $this->receiptRepository->create($receipt);
            
            // Create receipt items
            foreach ($items as $itemData) {
                $item = new ReceiptItem(
                    receiptId: $receiptId,
                    itemDescription: $itemData['description'],
                    quantity: $itemData['quantity'],
                    unitPriceChf: $itemData['unit_price_chf'],
                    lineTotalChf: $itemData['line_total_chf']
                );
                $this->receiptItemRepository->create($item);
            }
            
            $this->logger->info('Receipt created', [
                'document_id' => $documentId,
                'receipt_id' => $receiptId,
                'doc_number' => $docNumber,
                'total_amount' => $totalAmount,
                'created_by' => $currentUser->getId()
            ]);

            return $response->withHeader('Location', '/receipts/' . $documentId)->withStatus(302);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create receipt', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId()
            ]);
            
            return $response->withHeader('Location', '/receipts?error=create')->withStatus(302);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];
        
        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_RECEIPT) {
                return $response->withHeader('Location', '/receipts?error=notfound')->withStatus(302);
            }
            
            $receipt = $this->receiptRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            
            if (!$receipt || !$branch) {
                return $response->withHeader('Location', '/receipts?error=incomplete')->withStatus(302);
            }
            
            return $this->render($response, 'receipts/show.html.twig', [
                'user' => $currentUser->toArray(),
                'document' => $document->toArray(),
                'receipt' => $receipt->toArray(),
                'branch' => $branch->toArray(),
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch receipt details', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return $response->withHeader('Location', '/receipts?error=fetch')->withStatus(302);
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
            if (!$document || $document->getDocType() !== Document::TYPE_RECEIPT) {
                return $response->withHeader('Location', '/receipts?error=notfound')->withStatus(302);
            }
            
            $receipt = $this->receiptRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            
            if (!$receipt || !$branch) {
                return $response->withHeader('Location', '/receipts?error=incomplete')->withStatus(302);
            }

            // Generate PDF
            $pdfContent = $this->pdfService->generateReceiptPdf($document, $receipt, $branch);
            $filename = $this->pdfService->getReceiptPdfFilename($document);

            // Set headers for PDF download
            $response = $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) strlen($pdfContent));

            $response->getBody()->write($pdfContent);

            $this->logger->info('Receipt PDF downloaded', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'filename' => $filename
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Receipt PDF generation failed', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $response->withHeader('Location', '/receipts?error=pdf_generation')->withStatus(302);
        }
    }

    private function validateReceiptInput(array $data): array
    {
        $errors = [];
        
        // Branch
        if (empty($data['branch_id'] ?? '')) {
            $errors['branch_id'] = 'Filiale ist erforderlich';
        }
        
        // Items validation
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $errors['items'] = 'Mindestens eine Position ist erforderlich';
        } else {
            foreach ($items as $index => $item) {
                if (empty(trim($item['description'] ?? ''))) {
                    $errors["items.{$index}.description"] = 'Produktbeschreibung ist erforderlich';
                }
                
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($quantity < 1) {
                    $errors["items.{$index}.quantity"] = 'Menge muss mindestens 1 sein';
                }
                
                $unitPrice = (float) ($item['unit_price_chf'] ?? 0);
                if ($unitPrice <= 0) {
                    $errors["items.{$index}.unit_price_chf"] = 'Einzelpreis muss grösser als 0 sein';
                }
            }
        }
        
        return $errors;
    }

    private function parseReceiptItems(array $itemsData): array
    {
        $items = [];
        
        foreach ($itemsData as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price_chf'] ?? 0);
            $lineTotal = ReceiptItem::calculateLineTotal($quantity, $unitPrice);
            
            $items[] = [
                'description' => trim($item['description'] ?? ''),
                'quantity' => $quantity,
                'unit_price_chf' => $unitPrice,
                'line_total_chf' => $lineTotal,
            ];
        }
        
        return $items;
    }
}