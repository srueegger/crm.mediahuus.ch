<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\CsrfTokenManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response as SlimResponse;
use Twig\Environment;

class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const MESSAGE_FORBIDDEN = 'Die Sitzung ist abgelaufen oder das Formular ist veraltet. Bitte Seite neu laden und erneut versuchen.';
    private const MESSAGE_LOGIN_EXPIRED = 'Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.';
    private const MESSAGE_TOO_LARGE = 'Die hochgeladenen Dateien sind zu gross.';

    private int $postMaxBytes;

    public function __construct(
        private CsrfTokenManager $tokens,
        private Environment $twig,
        private LoggerInterface $logger,
        ?int $postMaxBytes = null
    ) {
        $this->postMaxBytes = $postMaxBytes ?? self::parseSize((string) ini_get('post_max_size'));
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (in_array(strtoupper($request->getMethod()), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        if ($this->postMaxBytes > 0 && (int) $request->getHeaderLine('Content-Length') > $this->postMaxBytes) {
            return $this->reject($request, 413, self::MESSAGE_TOO_LARGE);
        }

        $body = $request->getParsedBody();
        $token = $request->getHeaderLine('X-CSRF-Token');
        if ($token === '') {
            $token = is_array($body) ? ($body['_csrf'] ?? null) : null;
        }

        if ($this->tokens->isValid($token)) {
            return $handler->handle($request);
        }

        return $this->reject($request, 403, self::MESSAGE_FORBIDDEN);
    }

    private function reject(Request $request, int $status, string $message): Response
    {
        $this->logger->warning('CSRF-Pruefung fehlgeschlagen', [
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'status' => $status,
        ]);

        if ($status === 403 && !$this->wantsJson($request)
            && strtoupper($request->getMethod()) === 'POST'
            && $request->getUri()->getPath() === '/login') {
            $body = $request->getParsedBody();
            $email = is_array($body) && is_string($body['email'] ?? null) ? trim($body['email']) : '';
            $response = new SlimResponse(403);
            $response->getBody()->write($this->twig->render('auth/login.html.twig', [
                'error' => self::MESSAGE_LOGIN_EXPIRED,
                'email' => $email,
            ]));

            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }

        return $this->errorResponse($request, $status, $message);
    }

    private function wantsJson(Request $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'application/json')
            || $request->hasHeader('X-CSRF-Token');
    }

    private function errorResponse(Request $request, int $status, string $message): Response
    {
        $response = new SlimResponse($status);

        if ($this->wantsJson($request)) {
            $response->getBody()->write('{"success":false,"error":"csrf"}');
            return $response->withHeader('Content-Type', 'application/json');
        }

        $html = $this->twig->render('errors/csrf.html.twig', [
            'title' => $status === 413 ? 'Dateien zu gross' : 'Sitzung abgelaufen',
            'message' => $message,
            'back_url' => $this->backUrl($request),
        ]);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function backUrl(Request $request): string
    {
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return '/';
        }

        $parts = parse_url($referer);
        if (!is_array($parts) || ($parts['host'] ?? null) !== $request->getUri()->getHost()) {
            return '/';
        }

        $path = $parts['path'] ?? '/';
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/';
    }

    public static function parseSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || (int) $value <= 0) {
            return 0;
        }

        $number = (int) $value;
        return match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
