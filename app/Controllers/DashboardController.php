<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\DocumentRepository;
use App\Repositories\BranchRepository;
use App\Repositories\EstimateRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    private DocumentRepository $documentRepository;
    private BranchRepository $branchRepository;
    private EstimateRepository $estimateRepository;

    public function __construct(
        DocumentRepository $documentRepository,
        BranchRepository $branchRepository,
        EstimateRepository $estimateRepository
    ) {
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
}