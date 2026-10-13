<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\User;
use App\Repositories\BranchRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DocumentRepository;
use App\Services\CustomerMergeService;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class CustomerController extends BaseController
{
    private const SEARCH_MIN_LENGTH = 2;
    private const SEARCH_LIMIT = 10;

    private const PER_PAGE = 25;

    private const MAX_LENGTHS = [
        'company_name' => 70,
        'name' => 255,
        'phone' => 20,
        'email' => 255,
        'street' => 255,
        'zip' => 16,
        'city' => 255,
    ];

    private const LABELS = [
        'company_name' => 'Firma',
        'name' => 'Name',
        'phone' => 'Telefon',
        'email' => 'E-Mail',
        'street' => 'Strasse',
        'zip' => 'PLZ',
        'city' => 'Ort',
        'notes' => 'Notizen',
    ];

    private const DOC_ROUTES = [
        Document::TYPE_ESTIMATE => 'estimates',
        Document::TYPE_RECEIPT => 'receipts',
        Document::TYPE_INVOICE => 'invoices',
        Document::TYPE_PURCHASE => 'purchases',
        Document::TYPE_INSURANCE => 'insurance',
    ];

    public function __construct(
        Environment $twig,
        private CustomerRepository $customers,
        private DocumentRepository $documents,
        private BranchRepository $branches,
        private LoggerInterface $logger,
        private CustomerMergeService $mergeService
    ) {
        parent::__construct($twig);
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $q = isset($params['q']) && is_string($params['q']) ? trim($params['q']) : '';
        $page = isset($params['page']) && is_string($params['page']) && ctype_digit($params['page'])
            ? max(1, (int) $params['page']) : 1;
        $error = isset($params['error']) && is_string($params['error']) ? $params['error'] : null;

        $result = $this->customers->paginate($q !== '' ? $q : null, $page, self::PER_PAGE);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        return $this->render($response, 'customers/index.html.twig', [
            'user' => $this->currentUser($request),
            'rows' => $result['items'],
            'total' => $result['total'],
            'q' => $q,
            'page' => $page,
            'pages' => $pages,
            'error' => $error,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $customer = $this->customers->findById((int) $args['id']);
        if ($customer === null) {
            return $response->withHeader('Location', '/customers?error=notfound')->withStatus(302);
        }

        $branchNames = [];
        foreach ($this->branches->findAll() as $branch) {
            $branchNames[$branch->getId()] = $branch->getName();
        }

        $documents = [];
        foreach ($this->documents->findByCustomerId((int) $customer->getId()) as $document) {
            $route = self::DOC_ROUTES[$document->getDocType()] ?? null;
            $documents[] = [
                'document' => $document->toArray(),
                'type_label' => $document->getTypeLabel(),
                'branch_name' => $branchNames[$document->getBranchId()] ?? '',
                'show_url' => $route !== null ? '/' . $route . '/' . $document->getId() : null,
                'pdf_url' => $route !== null ? '/' . $route . '/' . $document->getId() . '/pdf' : null,
            ];
        }

        $query = $request->getQueryParams();
        $success = $query['success'] ?? null;
        $error = $query['error'] ?? null;

        return $this->render($response, 'customers/show.html.twig', [
            'user' => $this->currentUser($request),
            'customer' => $customer->toArray(),
            'documents' => $documents,
            'success' => is_string($success) ? $success : null,
            'error' => is_string($error) ? $error : null,
        ]);
    }

    public function mergeForm(Request $request, Response $response, array $args): Response
    {
        $sourceId = (int) $args['id'];
        $source = $this->customers->findById($sourceId);
        if ($source === null) {
            return $response->withHeader('Location', '/customers?error=notfound')->withStatus(302);
        }

        $targetParam = $request->getQueryParams()['target'] ?? null;
        $targetId = is_string($targetParam) && ctype_digit($targetParam) ? (int) $targetParam : 0;
        $target = $targetId > 0 && $targetId !== $sourceId ? $this->customers->findById($targetId) : null;
        if ($target === null) {
            return $response->withHeader('Location', '/customers/' . $sourceId . '?error=merge')->withStatus(302);
        }

        return $this->render($response, 'customers/merge.html.twig', [
            'user' => $this->currentUser($request),
            'source' => $source->toArray(),
            'target' => $target->toArray(),
            'sourceDocCount' => count($this->documents->findByCustomerId($sourceId)),
            'targetDocCount' => count($this->documents->findByCustomerId($targetId)),
        ]);
    }

    public function merge(Request $request, Response $response, array $args): Response
    {
        $sourceId = (int) $args['id'];
        $body = $request->getParsedBody();
        $targetParam = is_array($body) ? ($body['target_id'] ?? null) : null;
        $targetId = is_string($targetParam) && ctype_digit($targetParam) ? (int) $targetParam : 0;

        try {
            $this->mergeService->merge($sourceId, $targetId);
        } catch (\InvalidArgumentException $e) {
            return $response->withHeader('Location', '/customers/' . $sourceId . '?error=merge')->withStatus(302);
        } catch (\Exception $e) {
            $this->logger->error('Failed to merge customers', ['source' => $sourceId, 'target' => $targetId, 'error' => $e->getMessage()]);

            return $response->withHeader('Location', '/customers/' . $sourceId . '?error=merge')->withStatus(302);
        }

        $this->logger->info('Customers merged', ['source' => $sourceId, 'target' => $targetId]);

        return $response->withHeader('Location', '/customers/' . $targetId . '?success=merged')->withStatus(302);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->renderForm($request, $response, null, [], []);
    }

    public function store(Request $request, Response $response): Response
    {
        [$data, $errors] = $this->validate($request->getParsedBody());
        if ($errors !== []) {
            return $this->renderForm($request, $response, null, $data, $errors);
        }

        try {
            $id = $this->customers->create($this->buildCustomer(null, $data));
        } catch (\Exception $e) {
            $this->logger->error('Failed to create customer', ['error' => $e->getMessage()]);

            return $this->renderForm($request, $response, null, $data, ['general' => 'Kunde konnte nicht gespeichert werden.']);
        }

        return $response->withHeader('Location', '/customers/' . $id . '?success=created')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $customer = $this->customers->findById((int) $args['id']);
        if ($customer === null) {
            return $response->withHeader('Location', '/customers?error=notfound')->withStatus(302);
        }

        return $this->renderForm($request, $response, $customer, $customer->toArray(), []);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $customer = $this->customers->findById((int) $args['id']);
        if ($customer === null) {
            return $response->withHeader('Location', '/customers?error=notfound')->withStatus(302);
        }

        [$data, $errors] = $this->validate($request->getParsedBody());
        if ($errors !== []) {
            return $this->renderForm($request, $response, $customer, $data, $errors);
        }

        try {
            $this->customers->update($this->buildCustomer($customer, $data));
        } catch (\Exception $e) {
            $this->logger->error('Failed to update customer', ['id' => $customer->getId(), 'error' => $e->getMessage()]);

            return $this->renderForm($request, $response, $customer, $data, ['general' => 'Kunde konnte nicht gespeichert werden.']);
        }

        return $response->withHeader('Location', '/customers/' . $customer->getId() . '?success=updated')->withStatus(302);
    }

    private function currentUser(Request $request): array
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');

        return $currentUser->toArray();
    }

    private function renderForm(Request $request, Response $response, ?Customer $customer, array $data, array $errors): Response
    {
        return $this->render($response, 'customers/form.html.twig', [
            'user' => $this->currentUser($request),
            'customer' => $customer?->toArray(),
            'formData' => $data,
            'errors' => $errors,
            'maxLengths' => self::MAX_LENGTHS,
        ]);
    }

    /**
     * @return array{0: array<string, ?string>, 1: array<string, string>}
     */
    private function validate(mixed $body): array
    {
        $body = is_array($body) ? $body : [];
        $data = [];
        foreach (array_keys(self::LABELS) as $field) {
            $value = $body[$field] ?? '';
            $value = is_string($value) ? trim($value) : '';
            $data[$field] = $value;
        }

        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Name ist erforderlich';
        }
        if ($data['phone'] !== '' && preg_match('/^[\d\s\+\-\(\)]+$/', $data['phone']) !== 1) {
            $errors['phone'] = 'Ungültiges Telefonnummer-Format';
        }
        if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
        }
        foreach (self::MAX_LENGTHS as $field => $max) {
            if (!isset($errors[$field]) && mb_strlen($data[$field]) > $max) {
                $errors[$field] = self::LABELS[$field] . ' ist zu lang';
            }
        }

        return [$data, $errors];
    }

    private function buildCustomer(?Customer $existing, array $data): Customer
    {
        $nullable = fn(string $field): ?string => $data[$field] === '' ? null : $data[$field];

        return new Customer(
            $existing?->getId(),
            $data['name'],
            $nullable('company_name'),
            $nullable('phone'),
            $nullable('email'),
            $nullable('street'),
            $nullable('zip'),
            $nullable('city'),
            $nullable('notes'),
            $existing?->getCreatedAt()
        );
    }

    /**
     * Kundensuche für den Picker in den Erfassungsformularen (JSON).
     */
    public function search(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';

        $result = [];
        if (mb_strlen($query) >= self::SEARCH_MIN_LENGTH) {
            foreach ($this->customers->search($query, self::SEARCH_LIMIT) as $customer) {
                $result[] = $customer->toArray();
            }
        }

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
