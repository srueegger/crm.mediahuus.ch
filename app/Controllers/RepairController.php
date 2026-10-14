<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Models\User;
use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\RepairEventRepository;
use App\Repositories\RepairRepository;
use App\Repositories\UserRepository;
use App\Services\AppUrl;
use App\Services\CustomerFieldMap;
use App\Services\CustomerFormPrefill;
use App\Services\DamageTypeService;
use App\Services\MailResult;
use App\Services\PdfService;
use App\Services\RepairInputValidator;
use App\Services\RepairService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class RepairController extends BaseController
{
    private const PER_PAGE = 25;
    private const NOTE_MAX_LENGTH = 500;

    private const EVENT_LABELS = [
        RepairEvent::TYPE_STATUS => 'Status',
        RepairEvent::TYPE_EMAIL_SENT => 'E-Mail gesendet',
        RepairEvent::TYPE_EMAIL_FAILED => 'E-Mail fehlgeschlagen',
        RepairEvent::TYPE_NOTE => 'Notiz',
    ];

    public function __construct(
        Environment $twig,
        private RepairService $repairService,
        private RepairInputValidator $validator,
        private RepairRepository $repairs,
        private RepairEventRepository $events,
        private DocumentRepository $documents,
        private BranchRepository $branches,
        private UserRepository $users,
        private PdfService $pdfService,
        private AppUrl $appUrl,
        private CustomerFormPrefill $customerFormPrefill,
        private LoggerInterface $logger
    ) {
        parent::__construct($twig);
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $scope = ($params['scope'] ?? 'open') === 'all' ? 'all' : 'open';
        $status = is_string($params['status'] ?? null) && Repair::isValidStatus($params['status'])
            ? $params['status'] : '';
        $branchId = is_string($params['branch_id'] ?? null) && ctype_digit($params['branch_id'])
            ? (int) $params['branch_id'] : 0;
        $page = is_string($params['page'] ?? null) && ctype_digit($params['page'])
            ? min(100000, max(1, (int) $params['page'])) : 1;

        $branchNames = $this->branchNames();
        $result = $this->repairs->findList(
            ['scope' => $scope, 'status' => $status, 'branch_id' => $branchId ?: null],
            $page,
            self::PER_PAGE
        );

        $rows = [];
        foreach ($result['items'] as $item) {
            $rows[] = [
                'document' => $item['document']->toArray(),
                'repair' => $item['repair']->toArray(),
                'branch_name' => $branchNames[$item['document']->getBranchId()] ?? '',
            ];
        }

        $query = ['scope' => $scope, 'status' => $status, 'branch_id' => $branchId ?: ''];

        return $this->render($response, 'repairs/index.html.twig', [
            'user' => $this->currentUser($request)->toArray(),
            'rows' => $rows,
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'scope' => $scope,
            'status' => $status,
            'branchId' => $branchId,
            'query' => $query,
            'branches' => array_map(static fn ($branch) => $branch->toArray(), $this->branches->findAll()),
            'statusLabels' => Repair::STATUS_LABELS,
            'error' => is_string($params['error'] ?? null) ? $params['error'] : null,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $prefill = $this->customerFormPrefill->forRequest(
            Document::TYPE_REPAIR,
            $request->getQueryParams()['customer_id'] ?? null
        );

        return $this->renderForm($request, $response, [
            'mode' => 'create',
            'formData' => $prefill['formData'],
            'linkedCustomer' => $prefill['linkedCustomer'],
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $currentUser = $this->currentUser($request);
        $data = $this->formData($request);

        $errors = $this->validate($data);
        if ($errors !== []) {
            return $this->renderForm($request, $response, [
                'mode' => 'create',
                'errors' => $errors,
                'formData' => $data,
                'linkedCustomer' => $this->customerFormPrefill->linked($data['customer_id'] ?? null),
            ]);
        }

        try {
            $result = $this->repairService->create($data, $this->selectedCustomerId($data), (int) $currentUser->getId());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create repair', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId(),
            ]);

            return $this->redirect($response, '/repairs?error=create');
        }

        $this->logger->info('Repair created', [
            'document_id' => $result->documentId,
            'created_by' => $currentUser->getId(),
        ]);

        return $this->redirect(
            $response,
            '/repairs/' . $result->documentId . ($result->mail === null || $result->mail->isSent() ? '' : $this->mailQuery($result->mail, false))
        );
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $loaded = $this->load((int) $args['id']);
        if ($loaded === null) {
            return $this->redirect($response, '/repairs?error=notfound');
        }
        [$document, $repair] = $loaded;

        $branch = $this->branches->findById($document->getBranchId());
        $userNames = [];
        foreach ($this->users->findAll(true) as $user) {
            $userNames[$user->getId()] = $user->getName();
        }

        $history = [];
        foreach ($this->events->findByRepairId((int) $repair->getId()) as $event) {
            $label = self::EVENT_LABELS[$event->getEventType()] ?? $event->getEventType();
            if ($event->getEventType() === RepairEvent::TYPE_STATUS && $event->getStatus() !== null) {
                $label .= ': ' . (Repair::STATUS_LABELS[$event->getStatus()] ?? $event->getStatus());
            }
            $history[] = [
                'created_at' => $event->getCreatedAt(),
                'user_name' => $event->getUserId() !== null ? ($userNames[$event->getUserId()] ?? '') : '',
                'label' => $label,
                'event_type' => $event->getEventType(),
                'message' => $event->getMessage(),
            ];
        }

        $params = $request->getQueryParams();
        $damageTypes = DamageTypeService::getDamageTypes();

        return $this->render($response, 'repairs/show.html.twig', [
            'user' => $this->currentUser($request)->toArray(),
            'document' => $document->toArray(),
            'repair' => $repair->toArray(),
            'branch' => $branch?->toArray(),
            'damageTypeLabel' => $repair->getDamageType() !== null
                ? ($damageTypes[$repair->getDamageType()] ?? $repair->getDamageType()) : null,
            'history' => $history,
            'statusUrl' => $this->appUrl->repairStatus($repair->getStatusToken()),
            'statusLabels' => Repair::STATUS_LABELS,
            'mail' => is_string($params['mail'] ?? null) ? $params['mail'] : null,
            'error' => is_string($params['error'] ?? null) ? $params['error'] : null,
        ]);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $loaded = $this->load((int) $args['id']);
        if ($loaded === null) {
            return $this->redirect($response, '/repairs?error=notfound');
        }
        [$document, $repair] = $loaded;

        $formData = [
            'branch_id' => (string) $document->getBranchId(),
            'customer_id' => $document->getCustomerId() !== null ? (string) $document->getCustomerId() : '',
            'customer_name' => $document->getCustomerName(),
            'customer_phone' => $document->getCustomerPhone() ?? '',
            'customer_email' => $document->getCustomerEmail() ?? '',
            'device_name' => $repair->getDeviceName(),
            'imei_serial' => $repair->getImeiSerial() ?? '',
            'damage_type' => $repair->getDamageType() ?? '',
            'issue_text' => $repair->getIssueText(),
            'work_text' => $repair->getWorkText() ?? '',
            'price_chf' => $repair->getPriceChf() ?? '',
            'due_date' => $repair->getDueDate()?->format('Y-m-d') ?? '',
            'accessories' => $repair->getAccessories() ?? '',
            'unlock_code' => $repair->getUnlockCode() ?? '',
        ];

        return $this->renderForm($request, $response, [
            'mode' => 'edit',
            'document' => $document->toArray(),
            'repair' => $repair->toArray(),
            'formData' => $formData,
            'linkedCustomer' => $this->customerFormPrefill->linked($document->getCustomerId()),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $documentId = (int) $args['id'];
        $loaded = $this->load($documentId);
        if ($loaded === null) {
            return $this->redirect($response, '/repairs?error=notfound');
        }
        [$document, $repair] = $loaded;

        $currentUser = $this->currentUser($request);
        $data = $this->formData($request);

        $errors = $this->validate($data);
        if ($errors !== []) {
            return $this->renderForm($request, $response, [
                'mode' => 'edit',
                'document' => $document->toArray(),
                'repair' => $repair->toArray(),
                'errors' => $errors,
                'formData' => $data,
                'linkedCustomer' => $this->customerFormPrefill->linked($data['customer_id'] ?? null),
            ]);
        }

        try {
            $this->repairService->update($documentId, $data, $this->selectedCustomerId($data), (int) $currentUser->getId());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update repair', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'updated_by' => $currentUser->getId(),
            ]);

            return $this->redirect($response, '/repairs/' . $documentId . '?error=update');
        }

        return $this->redirect($response, '/repairs/' . $documentId);
    }

    public function changeStatus(Request $request, Response $response, array $args): Response
    {
        $documentId = (int) $args['id'];
        if ($this->load($documentId) === null) {
            return $this->redirect($response, '/repairs?error=notfound');
        }

        $currentUser = $this->currentUser($request);
        $data = $this->formData($request);
        $status = is_string($data['status'] ?? null) ? $data['status'] : '';
        if (!Repair::isValidStatus($status)) {
            return $this->redirect($response, '/repairs/' . $documentId . '?error=status');
        }
        $note = is_string($data['note'] ?? null) ? trim($data['note']) : '';
        $note = $note !== '' ? mb_substr($note, 0, self::NOTE_MAX_LENGTH) : null;

        try {
            $mail = $this->repairService->changeStatus($documentId, $status, $note, (int) $currentUser->getId());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to change repair status', [
                'document_id' => $documentId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return $this->redirect($response, '/repairs/' . $documentId . '?error=status');
        }

        return $this->redirect($response, '/repairs/' . $documentId . $this->mailQuery($mail, false));
    }

    public function sendEmail(Request $request, Response $response, array $args): Response
    {
        $documentId = (int) $args['id'];
        $loaded = $this->load($documentId);
        if ($loaded === null) {
            return $this->redirect($response, '/repairs?error=notfound');
        }
        [$document] = $loaded;

        $data = $this->formData($request);
        $type = is_string($data['type'] ?? null) ? $data['type'] : '';
        if ($type !== 'created' && $type !== 'ready') {
            return $this->redirect($response, '/repairs/' . $documentId . '?error=mailtype');
        }
        if (trim((string) $document->getCustomerEmail()) === '') {
            return $this->redirect($response, '/repairs/' . $documentId . '?mail=none');
        }

        try {
            $mail = $this->repairService->resend($documentId, $type, (int) $this->currentUser($request)->getId());
        } catch (\Throwable $e) {
            $this->logger->error('Failed to resend repair email', [
                'document_id' => $documentId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            $mail = MailResult::failed($e->getMessage());
        }

        return $this->redirect($response, '/repairs/' . $documentId . $this->mailQuery($mail, true));
    }

    public function generatePdf(Request $request, Response $response, array $args): Response
    {
        $documentId = (int) $args['id'];
        $currentUser = $this->currentUser($request);

        try {
            $loaded = $this->load($documentId);
            if ($loaded === null) {
                return $this->redirect($response, '/repairs?error=notfound');
            }
            [$document, $repair] = $loaded;

            $branch = $this->branches->findById($document->getBranchId());
            if ($branch === null) {
                return $this->redirect($response, '/repairs?error=incomplete');
            }

            $pdfContent = $this->pdfService->generateRepairPdf(
                $document,
                $repair,
                $branch,
                $this->appUrl->repairStatus($repair->getStatusToken())
            );
            $filename = $this->pdfService->getRepairPdfFilename($document);

            $response = $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) strlen($pdfContent));
            $response->getBody()->write($pdfContent);

            $this->logger->info('PDF downloaded', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'filename' => $filename,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('PDF generation failed', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->redirect($response, '/repairs?error=pdf_generation');
        }
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderForm(Request $request, Response $response, array $vars): Response
    {
        return $this->render($response, 'repairs/create.html.twig', $vars + [
            'user' => $this->currentUser($request)->toArray(),
            'branches' => array_map(static fn ($branch) => $branch->toArray(), $this->branches->findAll()),
            'damageTypes' => DamageTypeService::getDamageTypes(),
            'customerFieldMap' => CustomerFieldMap::for(Document::TYPE_REPAIR),
            'errors' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $errors = $this->validator->validate($data);
        if (!isset($errors['branch_id']) && $this->branches->findById((int) $data['branch_id']) === null) {
            $errors['branch_id'] = 'Bitte eine gültige Filiale wählen.';
        }

        return $errors;
    }

    /**
     * @return array{0: Document, 1: Repair}|null
     */
    private function load(int $documentId): ?array
    {
        $document = $this->documents->findById($documentId);
        if ($document === null || $document->getDocType() !== Document::TYPE_REPAIR) {
            return null;
        }
        $repair = $this->repairs->findByDocumentId($documentId);

        return $repair !== null ? [$document, $repair] : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $data = $request->getParsedBody();

        return is_array($data) ? $data : [];
    }

    private function mailQuery(?MailResult $mail, bool $noneIfNull): string
    {
        if ($mail === null) {
            return $noneIfNull ? '?mail=none' : '';
        }

        if ($mail->isSent()) {
            return '?mail=sent';
        }

        return $mail->status === MailResult::NOT_CONFIGURED ? '?mail=notconfigured' : '?mail=failed';
    }

    /**
     * @return array<int, string>
     */
    private function branchNames(): array
    {
        $names = [];
        foreach ($this->branches->findAll() as $branch) {
            $names[$branch->getId()] = $branch->getName();
        }

        return $names;
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('current_user');

        return $user;
    }

    private function redirect(Response $response, string $location): Response
    {
        return $response->withHeader('Location', $location)->withStatus(302);
    }
}
