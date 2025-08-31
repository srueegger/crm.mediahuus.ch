<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

class AuthController extends BaseController
{
    public function showLogin(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        if (!empty($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        return $this->render($response, 'auth/login.html.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        // TODO: Implement proper authentication
        // For now, simple hardcoded check for demo
        if ($email === 'admin@mediahuus.ch' && $password === 'admin123') {
            session_start();
            $_SESSION['user_id'] = 1;
            $_SESSION['user'] = [
                'id' => 1,
                'name' => 'Admin User',
                'email' => $email
            ];
            
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $this->render($response, 'auth/login.html.twig', [
            'error' => 'Ungültige Anmeldedaten',
            'email' => $email,
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_start();
        session_destroy();
        
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}