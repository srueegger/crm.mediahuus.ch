<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private const DEFAULT_SESSION_NAME = 'mediahuus_crm_session';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.use_strict_mode', '1');

            $name = $_ENV['SESSION_NAME'] ?? '';
            session_name(is_string($name) && $name !== '' ? $name : self::DEFAULT_SESSION_NAME);
            session_set_cookie_params(self::cookieParams($request));
            session_start();
        }

        return $handler->handle($request);
    }

    /**
     * @return array{lifetime: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    public static function cookieParams(ServerRequestInterface $request): array
    {
        $forwarded = strtolower(trim(explode(',', $request->getHeaderLine('X-Forwarded-Proto'))[0]));
        $secure = $request->getUri()->getScheme() === 'https' || $forwarded === 'https';

        return [
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}
