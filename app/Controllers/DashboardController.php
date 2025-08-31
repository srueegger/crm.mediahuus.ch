<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        /** @var User|null $currentUser */
        $currentUser = $request->getAttribute('current_user');
        
        return $this->render($response, 'dashboard.html.twig', [
            'user' => $currentUser ? $currentUser->toArray() : null,
        ]);
    }
}