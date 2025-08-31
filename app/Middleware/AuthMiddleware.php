<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        session_start();
        
        if (empty($_SESSION['user_id'])) {
            $response = new SlimResponse();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        return $handler->handle($request);
    }
}