<?php
declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use App\Repositories\UserRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\EstimateRepository;
use App\Repositories\BranchRepository;
use App\Services\AuthService;
use App\Services\PdfService;
use App\Controllers\UserController;
use App\Controllers\EstimateController;
use App\Controllers\DashboardController;

return [
    // Twig Template Engine
    Environment::class => function () {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $twig = new Environment($loader, [
            'cache' => $_ENV['TWIG_CACHE'] ?? false,
            'debug' => $_ENV['DEBUG'] ?? false,
        ]);
        
        // Add global variables
        $twig->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'Mediahuus CRM');
        
        // Add simple url_for function
        $twig->addFunction(new \Twig\TwigFunction('url_for', function ($routeName, $params = []) {
            // Simple hardcoded URLs for now
            $urls = [
                'estimate.new' => '/estimates/create',
                'estimates.index' => '/estimates',
                'estimates.create' => '/estimates/create',
                'estimates.show' => '/estimates/' . ($params['id'] ?? '{id}'),
                'estimates.pdf' => '/estimates/' . ($params['id'] ?? '{id}') . '/pdf',
                'purchase.new' => '/purchase/new',
                'insurance.new' => '/insurance/new',
                'users.index' => '/users',
                'users.create' => '/users/create',
                'users.edit' => '/users/' . ($params['id'] ?? '{id}') . '/edit',
                'users.update' => '/users/' . ($params['id'] ?? '{id}'),
                'users.toggle' => '/users/' . ($params['id'] ?? '{id}') . '/toggle-status',
            ];
            return $urls[$routeName] ?? '/';
        }));
        
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

    // Estimate Repository
    EstimateRepository::class => function (Connection $connection) {
        return new EstimateRepository($connection);
    },

    // Branch Repository
    BranchRepository::class => function (Connection $connection) {
        return new BranchRepository($connection);
    },

    // Estimate Controller
    EstimateController::class => function (Environment $twig, DocumentRepository $documentRepository, EstimateRepository $estimateRepository, BranchRepository $branchRepository, PdfService $pdfService, LoggerInterface $logger) {
        return new EstimateController($twig, $documentRepository, $estimateRepository, $branchRepository, $pdfService, $logger);
    },

    // Dashboard Controller
    DashboardController::class => function (Environment $twig, DocumentRepository $documentRepository, BranchRepository $branchRepository, EstimateRepository $estimateRepository) {
        return new DashboardController($twig, $documentRepository, $branchRepository, $estimateRepository);
    },
];