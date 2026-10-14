<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\RepairEventRepository;
use App\Repositories\RepairRepository;
use App\Services\RepairStatusPage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class RepairStatusController extends BaseController
{
    public function __construct(
        Environment $twig,
        private RepairRepository $repairs,
        private RepairEventRepository $events,
        private DocumentRepository $documents,
        private BranchRepository $branches,
        private RepairStatusPage $statusPage
    ) {
        parent::__construct($twig);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $repair = $this->repairs->findByToken((string) ($args['token'] ?? ''));
        $document = $repair ? $this->documents->findById($repair->getDocumentId()) : null;
        $branch = $document ? $this->branches->findById($document->getBranchId()) : null;

        if ($repair === null || $document === null || $branch === null) {
            return $this->respond($response->withStatus(404), 'public/not_found.html.twig');
        }

        $page = $this->statusPage->build(
            $document,
            $repair,
            $this->events->findByRepairId((int) $repair->getId()),
            $branch
        );

        if ($this->statusPage->isExpired($repair, new \DateTimeImmutable())) {
            return $this->respond(
                $response->withStatus(410),
                'public/repair_status_gone.html.twig',
                ['branch' => $page['branch']]
            );
        }

        return $this->respond($response, 'public/repair_status.html.twig', ['page' => $page]);
    }

    private function respond(Response $response, string $template, array $data = []): Response
    {
        return $this->render($response, $template, $data)
            ->withHeader('X-Robots-Tag', 'noindex, nofollow')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Cache-Control', 'no-store');
    }
}
