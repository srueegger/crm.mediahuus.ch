<?php
declare(strict_types=1);

namespace Tests\Middleware;

use App\Middleware\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class SessionMiddlewareTest extends TestCase
{
    public function testCookieParamsHttp(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://crm.test/');

        $this->assertSame(
            ['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax'],
            SessionMiddleware::cookieParams($request)
        );
    }

    public function testCookieParamsHttps(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://crm.test/');

        $this->assertTrue(SessionMiddleware::cookieParams($request)['secure']);
    }

    public function testCookieParamsForwardedProto(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'http://crm.test/')
            ->withHeader('X-Forwarded-Proto', 'HTTPS, http');

        $this->assertTrue(SessionMiddleware::cookieParams($request)['secure']);
    }
}
