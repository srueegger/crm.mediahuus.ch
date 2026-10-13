<?php
declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Services\TwigCacheConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use App\Repositories\UserRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\EstimateRepository;
use App\Repositories\BranchRepository;
use App\Repositories\ReceiptRepository;
use App\Repositories\ReceiptItemRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\InsuranceAssessmentRepository;
use App\Services\AuthService;
use App\Services\CustomerFormPrefill;
use App\Services\CustomerResolver;
use App\Services\CsrfTokenManager;
use App\Services\PdfService;
use App\Services\FileUploadService;
use App\Repositories\InvoiceRepository;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceInputValidator;
use App\Services\InvoiceNumberGenerator;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\QrBillFactory;
use App\Services\MailService;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use App\Controllers\UserController;
use App\Controllers\EstimateController;
use App\Controllers\ReceiptController;
use App\Controllers\PurchaseController;
use App\Controllers\InvoiceController;
use App\Controllers\InsuranceController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;

return [
    // Twig Template Engine
    Environment::class => function (CsrfTokenManager $csrf) {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $twig = new Environment($loader, [
            'cache' => TwigCacheConfig::resolve($_ENV['TWIG_CACHE'] ?? null, dirname(__DIR__)),
            'debug' => filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            // Geänderte Templates nach einem Deploy immer neu kompilieren
            'auto_reload' => true,
        ]);
        
        // Add global variables
        $twig->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Mediahuus CRM');
        $twig->addExtension(new \App\Twig\NavigationExtension());
        
        // Add simple url_for function
        $twig->addFunction(new \Twig\TwigFunction('url_for', function ($routeName, $params = []) {
            // Simple hardcoded URLs for now
            $urls = [
                'estimate.new' => '/estimates/create',
                'estimates.create' => '/estimates/create',
                'estimates.show' => '/estimates/' . ($params['id'] ?? '{id}'),
                'estimates.pdf' => '/estimates/' . ($params['id'] ?? '{id}') . '/pdf',
                'receipts.create' => '/receipts/create',
                'receipts.show' => '/receipts/' . ($params['id'] ?? '{id}'),
                'receipts.pdf' => '/receipts/' . ($params['id'] ?? '{id}') . '/pdf',
                'purchases.create' => '/purchases/create',
                'purchases.show' => '/purchases/' . ($params['id'] ?? '{id}'),
                'purchases.pdf' => '/purchases/' . ($params['id'] ?? '{id}') . '/pdf',
                'customers.index' => '/customers',
                'customers.search' => '/customers/search',
                'customers.create' => '/customers/create',
                'customers.store' => '/customers',
                'customers.show' => '/customers/' . ($params['id'] ?? '{id}'),
                'customers.edit' => '/customers/' . ($params['id'] ?? '{id}') . '/edit',
                'customers.update' => '/customers/' . ($params['id'] ?? '{id}'),
                'customers.merge' => '/customers/' . ($params['id'] ?? '{id}') . '/merge',
                'invoices.create' => '/invoices/create',
                'invoices.store' => '/invoices',
                'invoices.show' => '/invoices/' . ($params['id'] ?? '{id}'),
                'invoices.pdf' => '/invoices/' . ($params['id'] ?? '{id}') . '/pdf',
                'insurance.create' => '/insurance/create',
                'insurance.show' => '/insurance/' . ($params['id'] ?? '{id}'),
                'insurance.pdf' => '/insurance/' . ($params['id'] ?? '{id}') . '/pdf',
                'export.excel' => '/export/excel',
                'users.index' => '/users',
                'users.create' => '/users/create',
                'users.edit' => '/users/' . ($params['id'] ?? '{id}') . '/edit',
                'users.update' => '/users/' . ($params['id'] ?? '{id}'),
                'users.toggle' => '/users/' . ($params['id'] ?? '{id}') . '/toggle-status',
            ];
            return $urls[$routeName] ?? '/';
        }));
        
        $twig->addFunction(new \Twig\TwigFunction('csrf_token', function () use ($csrf): string {
            return $csrf->getToken();
        }));
        $twig->addFunction(new \Twig\TwigFunction('csrf_field', function () use ($csrf): string {
            return '<input type="hidden" name="_csrf" value="'
                . htmlspecialchars($csrf->getToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        }, ['is_safe' => ['html']]));

        return $twig;
    },

    // Database Connection
    Connection::class => function () {
        $connectionParams = [
            'dbname' => $_ENV['DB_DATABASE'] ?? 'crm_mediahuus',
            'user' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? 'root',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];
        
        return DriverManager::getConnection($connectionParams);
    },

    // Logger
    LoggerInterface::class => function () {
        $logger = new Logger('crm');
        $handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::INFO);
        $logger->pushHandler($handler);
        
        return $logger;
    },

    // User Repository
    UserRepository::class => function (Connection $connection) {
        return new UserRepository($connection);
    },

    // Auth Service
    AuthService::class => function (UserRepository $userRepository, LoggerInterface $logger) {
        return new AuthService($userRepository, $logger);
    },

    // Mail Service (zentraler E-Mail-Versand)
    MailService::class => function (LoggerInterface $logger) {
        $dsn = trim((string) ($_ENV['MAILER_DSN'] ?? ''));
        $transport = $dsn !== '' ? Transport::fromDsn($dsn) : null;
        $from = new Address($_ENV['MAIL_FROM'] ?? 'info@mediahuus.ch', $_ENV['MAIL_FROM_NAME'] ?? 'Mediahuus');
        return new MailService($transport, $from, $logger);
    },

    // PDF Service
    PdfService::class => function (LoggerInterface $logger) {
        return new PdfService($logger);
    },

    // User Controller  
    UserController::class => function (Environment $twig, UserRepository $userRepository, LoggerInterface $logger) {
        return new UserController($twig, $userRepository, $logger);
    },

    // Document Repository
    DocumentRepository::class => function (Connection $connection) {
        return new DocumentRepository($connection);
    },

    // Customer Repository
    CustomerRepository::class => function (Connection $connection) {
        return new CustomerRepository($connection);
    },

    // Estimate Repository
    EstimateRepository::class => function (Connection $connection) {
        return new EstimateRepository($connection);
    },

    // Branch Repository
    BranchRepository::class => function (Connection $connection) {
        return new BranchRepository($connection);
    },

    // Receipt Item Repository
    ReceiptItemRepository::class => function (Connection $connection) {
        return new ReceiptItemRepository($connection);
    },

    // Receipt Repository
    ReceiptRepository::class => function (Connection $connection, ReceiptItemRepository $receiptItemRepository) {
        return new ReceiptRepository($connection, $receiptItemRepository);
    },

    // Rechnungen: Konfiguration, Repository und Services
    InvoiceRepository::class => function (Connection $connection) {
        return new InvoiceRepository($connection);
    },

    InvoiceInputValidator::class => function (InvoiceCalculator $calculator) {
        return new InvoiceInputValidator(require __DIR__ . '/invoice.php', $calculator);
    },

    QrBillFactory::class => function () {
        $config = require __DIR__ . '/invoice.php';
        return new QrBillFactory($config['creditor']);
    },

    InvoicePdfService::class => function (QrBillFactory $qrBillFactory, LoggerInterface $logger) {
        return new InvoicePdfService(require __DIR__ . '/invoice.php', $qrBillFactory, $logger);
    },

    InvoiceService::class => function (
        Connection $connection,
        DocumentRepository $documentRepository,
        InvoiceRepository $invoiceRepository,
        BranchRepository $branchRepository,
        InvoiceCalculator $calculator,
        InvoiceNumberGenerator $numberGenerator,
        QrBillFactory $qrBillFactory,
        CustomerResolver $customerResolver
    ) {
        return new InvoiceService(
            $connection,
            $documentRepository,
            $invoiceRepository,
            $branchRepository,
            $calculator,
            $numberGenerator,
            $qrBillFactory,
            require __DIR__ . '/invoice.php',
            $customerResolver
        );
    },

    // Invoice Controller
    InvoiceController::class => function (
        Environment $twig,
        DocumentRepository $documentRepository,
        InvoiceRepository $invoiceRepository,
        BranchRepository $branchRepository,
        InvoiceInputValidator $validator,
        InvoiceService $invoiceService,
        InvoicePdfService $pdfService,
        LoggerInterface $logger,
        CustomerFormPrefill $customerFormPrefill
    ) {
        return new InvoiceController(
            $twig,
            $documentRepository,
            $invoiceRepository,
            $branchRepository,
            $validator,
            $invoiceService,
            $pdfService,
            require __DIR__ . '/invoice.php',
            $logger,
            $customerFormPrefill
        );
    },

    // Estimate Controller
    EstimateController::class => function (Environment $twig, DocumentRepository $documentRepository, EstimateRepository $estimateRepository, BranchRepository $branchRepository, PdfService $pdfService, LoggerInterface $logger, Connection $connection, CustomerResolver $customerResolver, CustomerFormPrefill $customerFormPrefill) {
        return new EstimateController($twig, $documentRepository, $estimateRepository, $branchRepository, $pdfService, $logger, $connection, $customerResolver, $customerFormPrefill);
    },

    // Receipt Controller
    ReceiptController::class => function (Environment $twig, DocumentRepository $documentRepository, ReceiptRepository $receiptRepository, ReceiptItemRepository $receiptItemRepository, BranchRepository $branchRepository, PdfService $pdfService, LoggerInterface $logger, Connection $connection, CustomerResolver $customerResolver, CustomerFormPrefill $customerFormPrefill) {
        return new ReceiptController($twig, $documentRepository, $receiptRepository, $receiptItemRepository, $branchRepository, $pdfService, $logger, $connection, $customerResolver, $customerFormPrefill);
    },

    // Purchase Repository
    PurchaseRepository::class => function (Connection $connection) {
        return new PurchaseRepository($connection);
    },

    // File Upload Service
    FileUploadService::class => function (LoggerInterface $logger) {
        $uploadDir = __DIR__ . '/../public/uploads/id_documents';
        return new FileUploadService($uploadDir, $logger);
    },

    // Purchase Controller
    PurchaseController::class => function (Environment $twig, DocumentRepository $documentRepository, PurchaseRepository $purchaseRepository, BranchRepository $branchRepository, PdfService $pdfService, FileUploadService $fileUploadService, LoggerInterface $logger, Connection $connection, CustomerResolver $customerResolver, CustomerFormPrefill $customerFormPrefill) {
        return new PurchaseController($twig, $documentRepository, $purchaseRepository, $branchRepository, $pdfService, $fileUploadService, $logger, $connection, $customerResolver, $customerFormPrefill);
    },

    // Insurance Assessment Repository
    InsuranceAssessmentRepository::class => function (Connection $connection) {
        return new InsuranceAssessmentRepository($connection);
    },

    // Insurance Controller
    InsuranceController::class => function (Environment $twig, DocumentRepository $documentRepository, InsuranceAssessmentRepository $insuranceRepository, BranchRepository $branchRepository, PdfService $pdfService, LoggerInterface $logger, Connection $connection, CustomerResolver $customerResolver, CustomerFormPrefill $customerFormPrefill) {
        return new InsuranceController($twig, $documentRepository, $insuranceRepository, $branchRepository, $pdfService, $logger, $connection, $customerResolver, $customerFormPrefill);
    },

    // Dashboard Controller
    DashboardController::class => function (Environment $twig, DocumentRepository $documentRepository, BranchRepository $branchRepository, EstimateRepository $estimateRepository, PurchaseRepository $purchaseRepository, ReceiptRepository $receiptRepository, InsuranceAssessmentRepository $insuranceRepository, InvoiceRepository $invoiceRepository) {
        return new DashboardController($twig, $documentRepository, $branchRepository, $estimateRepository, $purchaseRepository, $receiptRepository, $insuranceRepository, $invoiceRepository);
    },

    // Export Controller
    ExportController::class => function (DocumentRepository $documentRepository, BranchRepository $branchRepository, EstimateRepository $estimateRepository, PurchaseRepository $purchaseRepository, ReceiptRepository $receiptRepository, InsuranceAssessmentRepository $insuranceRepository, InvoiceRepository $invoiceRepository, UserRepository $userRepository, LoggerInterface $logger) {
        return new ExportController($documentRepository, $branchRepository, $estimateRepository, $purchaseRepository, $receiptRepository, $insuranceRepository, $invoiceRepository, $userRepository, $logger);
    },
];