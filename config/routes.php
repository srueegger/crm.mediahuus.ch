<?php
declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\EstimateController;
use App\Middleware\AuthMiddleware;
use Slim\App;

// Public routes
$app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
$app->post('/login', [AuthController::class, 'login']);

// Protected routes
$app->group('', function () use ($app) {
    $app->get('/', [DashboardController::class, 'index'])->setName('dashboard');
    $app->post('/logout', [AuthController::class, 'logout'])->setName('logout');
    $app->post('/clear-cache', [DashboardController::class, 'clearCache'])->setName('clear-cache');
    
    // User Management
    $app->get('/users', [UserController::class, 'index'])->setName('users.index');
    $app->get('/users/create', [UserController::class, 'create'])->setName('users.create');
    $app->post('/users', [UserController::class, 'store'])->setName('users.store');
    $app->get('/users/{id:[0-9]+}/edit', [UserController::class, 'edit'])->setName('users.edit');
    $app->post('/users/{id:[0-9]+}', [UserController::class, 'update'])->setName('users.update');
    $app->post('/users/{id:[0-9]+}/toggle-status', [UserController::class, 'toggleStatus'])->setName('users.toggle');
    
    // Estimates
    $app->get('/estimates', [EstimateController::class, 'index'])->setName('estimates.index');
    $app->get('/estimates/create', [EstimateController::class, 'create'])->setName('estimates.create');
    $app->post('/estimates', [EstimateController::class, 'store'])->setName('estimates.store');
    $app->get('/estimates/{id:[0-9]+}', [EstimateController::class, 'show'])->setName('estimates.show');
    $app->get('/estimates/{id:[0-9]+}/pdf', [EstimateController::class, 'generatePdf'])->setName('estimates.pdf');
    // Placeholder routes
    
    $app->get('/purchase/new', function ($request, $response) {
        return $response->withHeader('Location', '/')->withStatus(302);
    })->setName('purchase.new');
    
    $app->get('/insurance/new', function ($request, $response) {
        return $response->withHeader('Location', '/')->withStatus(302);
    })->setName('insurance.new');
    
})->add(AuthMiddleware::class);