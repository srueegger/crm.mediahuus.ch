<?php
declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\App;

// Public routes
$app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
$app->post('/login', [AuthController::class, 'login']);

// Protected routes
$app->group('', function () use ($app) {
    $app->get('/', [DashboardController::class, 'index'])->setName('dashboard');
    $app->post('/logout', [AuthController::class, 'logout'])->setName('logout');
    
    // User Management
    $app->get('/users', [UserController::class, 'index'])->setName('users.index');
    $app->get('/users/create', [UserController::class, 'create'])->setName('users.create');
    $app->post('/users', [UserController::class, 'store'])->setName('users.store');
    $app->get('/users/{id:[0-9]+}/edit', [UserController::class, 'edit'])->setName('users.edit');
    $app->post('/users/{id:[0-9]+}', [UserController::class, 'update'])->setName('users.update');
    $app->post('/users/{id:[0-9]+}/toggle-status', [UserController::class, 'toggleStatus'])->setName('users.toggle');
    
    // Placeholder routes
    $app->get('/estimate/new', function ($request, $response) {
        return $response->withHeader('Location', '/')->withStatus(302);
    })->setName('estimate.new');
    
    $app->get('/purchase/new', function ($request, $response) {
        return $response->withHeader('Location', '/')->withStatus(302);
    })->setName('purchase.new');
    
    $app->get('/insurance/new', function ($request, $response) {
        return $response->withHeader('Location', '/')->withStatus(302);
    })->setName('insurance.new');
    
})->add(AuthMiddleware::class);