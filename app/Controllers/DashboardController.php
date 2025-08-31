<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'dashboard.html.twig', [
            'user' => $_SESSION['user'] ?? null,
        ]);
    }
}