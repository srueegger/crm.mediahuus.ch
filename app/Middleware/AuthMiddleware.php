<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->authService->isLoggedIn()) {
            $response = new SlimResponse();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Add current user to request attributes for easy access in controllers
        $currentUser = $this->authService->getCurrentUser();
        if ($currentUser) {
            $request = $request->withAttribute('current_user', $currentUser);
        }

        return $handler->handle($request);
    }
}