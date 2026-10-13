<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CsrfTokenManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class AuthController extends BaseController
{
    private AuthService $authService;

    private CsrfTokenManager $csrf;

    public function __construct(Environment $twig, AuthService $authService, CsrfTokenManager $csrf)
    {
        parent::__construct($twig);
        $this->authService = $authService;
        $this->csrf = $csrf;
    }

    public function showLogin(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        if ($this->authService->isLoggedIn()) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        return $this->render($response, 'auth/login.html.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validate input
        if (empty($email) || empty($password)) {
            return $this->render($response, 'auth/login.html.twig', [
                'error' => 'Bitte geben Sie E-Mail und Passwort ein',
                'email' => $email,
            ]);
        }

        // Attempt authentication
        $user = $this->authService->authenticate($email, $password);
        
        if ($user) {
            $this->authService->startSession($user);
            $this->csrf->regenerate();
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        // Authentication failed
        return $this->render($response, 'auth/login.html.twig', [
            'error' => 'Ungültige Anmeldedaten',
            'email' => $email,
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->authService->endSession();
        $this->csrf->regenerate();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}