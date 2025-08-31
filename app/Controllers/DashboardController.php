<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\DocumentRepository;
use App\Repositories\BranchRepository;
use App\Repositories\EstimateRepository;
use Twig\Environment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    private DocumentRepository $documentRepository;
    private BranchRepository $branchRepository;
    private EstimateRepository $estimateRepository;

    public function __construct(
        Environment $twig,
        DocumentRepository $documentRepository,
        BranchRepository $branchRepository,
        EstimateRepository $estimateRepository
    ) {
        parent::__construct($twig);
        $this->documentRepository = $documentRepository;
        $this->branchRepository = $branchRepository;
        $this->estimateRepository = $estimateRepository;
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        // Get recent documents (limit to 5 most recent)
        $recentDocuments = [];
        $documents = $this->documentRepository->findAll();
        
        // Get the 5 most recent documents with their related data
        foreach (array_slice($documents, 0, 5) as $document) {
            $branch = $this->branchRepository->findById($document->getBranchId());
            
            $itemData = [
                'document' => $document,
                'branch' => $branch,
            ];
            
            // Get type-specific data
            if ($document->getDocType() === 'estimate') {
                $estimate = $this->estimateRepository->findByDocumentId($document->getId());
                $itemData['estimate'] = $estimate;
            }
            
            $recentDocuments[] = $itemData;
        }
        
        return $this->render($response, 'dashboard.html.twig', [
            'user' => $currentUser ? $currentUser->toArray() : null,
            'recent_documents' => $recentDocuments,
        ]);
    }

    public function clearCache(Request $request, Response $response): Response
    {
        try {
            // Clear Twig cache directory
            $cacheDir = __DIR__ . '/../../var/cache/twig';
            if (is_dir($cacheDir)) {
                $this->deleteDirectory($cacheDir);
            }
            
            // Also clear any false/ cache directories in public
            $publicCacheDir = __DIR__ . '/../../public/false';
            if (is_dir($publicCacheDir)) {
                $this->deleteDirectory($publicCacheDir);
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