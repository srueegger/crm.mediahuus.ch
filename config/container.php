<?php
declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

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
        $twig->addFunction(new \Twig\TwigFunction('url_for', function ($routeName) {
            // Simple hardcoded URLs for now
            $urls = [
                'estimate.new' => '/estimate/new',
                'purchase.new' => '/purchase/new',
                'insurance.new' => '/insurance/new',
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
];