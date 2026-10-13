<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\InvoiceRepository;
use App\Services\CustomerFieldMap;
use App\Services\CustomerFormPrefill;
use App\Services\InvoiceInputValidator;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class InvoiceController extends BaseController
{
    /**
     * @param array<string, mixed> $invoiceConfig Inhalt von config/invoice.php
     */
    public function __construct(
        Environment $twig,
        private DocumentRepository $documentRepository,
        private InvoiceRepository $invoiceRepository,
        private BranchRepository $branchRepository,
        private InvoiceInputValidator $validator,
        private InvoiceService $invoiceService,
        private InvoicePdfService $pdfService,
        private array $invoiceConfig,
        private LoggerInterface $logger,
        private CustomerFormPrefill $customerFormPrefill
    ) {
        parent::__construct($twig);
    }

    public function create(Request $request, Response $response): Response
    {
        $prefill = $this->customerFormPrefill->forRequest(
            Document::TYPE_INVOICE,
            $request->getQueryParams()['customer_id'] ?? null
        );

        return $this->renderForm($response, $request, $prefill['formData'], [], null, $prefill['linkedCustomer'], true);
    }

    public function store(Request $request, Response $response): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $post = $request->getParsedBody();
        $post = is_array($post) ? $post : [];

        $result = $this->validator->validate($post);

        if (!empty($result['errors'])) {
            return $this->renderForm($response, $request, $post, $result['errors'], null, $this->customerFormPrefill->linked($post['customer_id'] ?? null));
        }

        try {
            $documentId = $this->invoiceService->create($result['data'], $currentUser->getId(), $this->selectedCustomerId($post));
        } catch (\RuntimeException $e) {
            $this->logger->error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId(),
            ]);
            return $this->renderForm($response, $request, $post, [], $e->getMessage(), $this->customerFormPrefill->linked($post['customer_id'] ?? null));
        } catch (\Throwable $e) {
            $this->logger->error('Invoice creation failed unexpectedly', [
                'error' => $e->getMessage(),
                'created_by' => $currentUser->getId(),
            ]);
            return $this->renderForm(
                $response,
                $request,
                $post,
                [],
                'Die Rechnung konnte nicht gespeichert werden. Bitte erneut versuchen.',
                $this->customerFormPrefill->linked($post['customer_id'] ?? null)
            );
        }

        $this->logger->info('Invoice created', [
            'document_id' => $documentId,
            'created_by' => $currentUser->getId(),
        ]);

        return $response->withHeader('Location', '/invoices/' . $documentId)->withStatus(302);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');
        $documentId = (int) $args['id'];

        try {
            $document = $this->documentRepository->findById($documentId);
            if (!$document || $document->getDocType() !== Document::TYPE_INVOICE) {
                return $response->withHeader('Location', '/?error=notfound')->withStatus(302);
            }

            $invoice = $this->invoiceRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            if (!$invoice || !$branch) {
                return $response->withHeader('Location', '/?error=incomplete')->withStatus(302);
            }

            return $this->render($response, 'invoices/show.html.twig', [
                'user' => $currentUser->toArray(),
                'document' => $document->toArray(),
                'invoice' => $invoice->toArray(),
                'branch' => $branch->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch invoice details', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
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
            if (!$document || $document->getDocType() !== Document::TYPE_INVOICE) {
                return $response->withHeader('Location', '/?error=notfound')->withStatus(302);
            }

            $invoice = $this->invoiceRepository->findByDocumentId($documentId);
            $branch = $this->branchRepository->findById($document->getBranchId());
            if (!$invoice || !$branch) {
                return $response->withHeader('Location', '/?error=incomplete')->withStatus(302);
            }

            $pdfContent = $this->pdfService->generate($document, $invoice, $branch);
            $filename = $this->pdfService->getFilename($document);

            $response = $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) strlen($pdfContent));
            $response->getBody()->write($pdfContent);

            $this->logger->info('Invoice PDF downloaded', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'filename' => $filename,
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Invoice PDF generation failed', [
                'document_id' => $documentId,
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage(),
            ]);
            return $response->withHeader('Location', '/?error=pdf_generation')->withStatus(302);
        }
    }

    /**
     * @param array<string, mixed> $post Roh-POST (Positionszeilen behalten ihre Schlüssel)
     * @param array<string, string> $errors
     */
    private function renderForm(
        Response $response,
        Request $request,
        array $post,
        array $errors,
        ?string $error,
        ?array $linkedCustomer = null,
        bool $freshForm = false
    ): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('current_user');

        $rawItems = $post['items'] ?? [];
        $items = [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $key => $row) {
                if (is_array($row)) {
                    $items[(int) $key] = $row;
                }
            }
        }
        if ($items === []) {
            $items[0] = [];
        }

        $formData = [
            'company_name' => $this->scalar($post['company_name'] ?? ''),
            'contact_name' => $this->scalar($post['contact_name'] ?? ''),
            'street' => $this->scalar($post['street'] ?? ''),
            'zip' => $this->scalar($post['zip'] ?? ''),
            'city' => $this->scalar($post['city'] ?? ''),
            'customer_id' => $this->scalar($post['customer_id'] ?? ''),
            'invoice_date' => $freshForm ? date('Y-m-d') : $this->scalar($post['invoice_date'] ?? ''),
            'payment_term_days' => $freshForm
                ? (string) $this->invoiceConfig['default_payment_term_days']
                : $this->scalar($post['payment_term_days'] ?? ''),
            'items' => $items,
        ];

        return $this->render($response, 'invoices/create.html.twig', [
            'user' => $currentUser->toArray(),
            'formData' => $formData,
            'linkedCustomer' => $linkedCustomer,
            'customerFieldMap' => CustomerFieldMap::for(Document::TYPE_INVOICE),
            'errors' => $errors,
            'error' => $error,
            'vat_rates' => $this->invoiceConfig['vat_rates'],
            'default_vat_rate' => $this->invoiceConfig['default_vat_rate'],
        ]);
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
