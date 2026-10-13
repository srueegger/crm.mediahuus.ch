<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\DocumentRepository;
use App\Repositories\BranchRepository;
use App\Repositories\EstimateRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\ReceiptRepository;
use App\Repositories\InsuranceAssessmentRepository;
use App\Repositories\InvoiceRepository;
use App\Services\TwigCacheConfig;
use Twig\Environment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    private DocumentRepository $documentRepository;
    private BranchRepository $branchRepository;
    private EstimateRepository $estimateRepository;
    private PurchaseRepository $purchaseRepository;
    private ReceiptRepository $receiptRepository;
    private InsuranceAssessmentRepository $insuranceRepository;
    private InvoiceRepository $invoiceRepository;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        BranchRepository $branchRepository,
        EstimateRepository $estimateRepository,
        PurchaseRepository $purchaseRepository,
        ReceiptRepository $receiptRepository,
        InsuranceAssessmentRepository $insuranceRepository,
        InvoiceRepository $invoiceRepository
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->branchRepository = $branchRepository;
        $this->estimateRepository = $estimateRepository;
        $this->purchaseRepository = $purchaseRepository;
        $this->receiptRepository = $receiptRepository;
        $this->insuranceRepository = $insuranceRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $request->getAttribute('current_user');

        // Get query parameters for filtering
        $params = $request->getQueryParams();
        $filterType = $params['type'] ?? '';
        $filterBranch = $params['branch'] ?? '';

        // Get all documents (sorted by created_at DESC)
        $documents = $this->documentRepository->findAll();

        // Apply filters
        if ($filterType !== '') {
            $documents = array_filter($documents, function ($doc) use ($filterType) {
                return $doc->getDocType() === $filterType;
            });
        }
        if ($filterBranch !== '') {
            $branchId = (int) $filterBranch;
            $documents = array_filter($documents, function ($doc) use ($branchId) {
                return $doc->getBranchId() === $branchId;
            });
        }

        // Limit to 50 most recent
        $documents = array_slice(array_values($documents), 0, 50);

        // Get all branches for filter dropdown
        $branches = $this->branchRepository->findAll();

        // Enrich documents with type-specific data.
        // Twig (3.x) resolviert snake_case-Attribute wie `doc_number` nicht auf getDocNumber(),
        // daher – wie auf allen anderen Seiten – als toArray() übergeben.
        $allDocuments = [];
        foreach ($documents as $document) {
            $branch = $this->branchRepository->findById($document->getBranchId());

            $itemData = [
                'document' => $document->toArray(),
                'branch' => $branch ? $branch->toArray() : null,
            ];

            // Get type-specific data
            switch ($document->getDocType()) {
                case 'estimate':
                    $estimate = $this->estimateRepository->findByDocumentId($document->getId());
                    $itemData['estimate'] = $estimate ? $estimate->toArray() : null;
                    break;
                case 'purchase':
                    $purchase = $this->purchaseRepository->findByDocumentId($document->getId());
                    $itemData['purchase'] = $purchase ? $purchase->toArray() : null;
                    break;
                case 'receipt':
                    $receipt = $this->receiptRepository->findByDocumentId($document->getId());
                    $itemData['receipt'] = $receipt ? $receipt->toArray() : null;
                    break;
                case 'insurance':
                    $insurance = $this->insuranceRepository->findByDocumentId($document->getId());
                    $itemData['insurance'] = $insurance ? $insurance->toArray() : null;
                    break;
                case 'invoice':
                    $invoice = $this->invoiceRepository->findByDocumentId($document->getId());
                    $itemData['invoice'] = $invoice ? $invoice->toArray() : null;
                    break;
            }

            $allDocuments[] = $itemData;
        }

        return $this->render($response, 'dashboard.html.twig', [
            'user' => $currentUser ? $currentUser->toArray() : null,
            'documents' => $allDocuments,
            'branches' => $branches,
            'filter_type' => $filterType,
            'filter_branch' => $filterBranch,
            'total_count' => count($allDocuments),
        ]);
    }

    public function clearCache(Request $request, Response $response): Response
    {
        try {
            $projectRoot = dirname(__DIR__, 2);
            $cacheDirs = [
                TwigCacheConfig::defaultDir($projectRoot),
                // Altlast: TWIG_CACHE=false wurde früher als Pfad public/false gelesen
                $projectRoot . '/public/false',
            ];
            $configured = TwigCacheConfig::resolve($_ENV['TWIG_CACHE'] ?? null, $projectRoot);
            if ($configured !== false) {
                $cacheDirs[] = $configured;
            }

            foreach (array_unique($cacheDirs) as $cacheDir) {
                if (is_dir($cacheDir)) {
                    $this->deleteDirectory($cacheDir);
                }
            }
            
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Cache erfolgreich geleert']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Fehler beim Leeren des Caches: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}