<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Middleware\CsrfMiddleware;
use App\Middleware\SessionMiddleware;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Create container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Create app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Middleware (Slim: zuletzt hinzugefügt = zuerst ausgeführt)
// Reihenfolge der Ausführung: Error -> Session -> BodyParsing -> CSRF
$debug = filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
$app->add(CsrfMiddleware::class);
$app->addBodyParsingMiddleware();
$app->add(SessionMiddleware::class);
$app->addErrorMiddleware($debug, true, true);

// Load routes
$GLOBALS['app'] = $app;
require __DIR__ . '/../config/routes.php';

$app->run();