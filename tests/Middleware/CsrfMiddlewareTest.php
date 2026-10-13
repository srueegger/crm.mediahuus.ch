<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Middleware\CsrfMiddleware;
use App\Services\CsrfTokenManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class CsrfMiddlewareTest extends TestCase
{
    private CsrfTokenManager $tokens;
    private Environment $twig;
    private LoggerInterface|MockObject $logger;
    private object $handler;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->tokens = new CsrfTokenManager();
        $this->twig = new Environment(new ArrayLoader([
            'errors/csrf.html.twig' => '{{ title }}|{{ message }}|{{ back_url }}',
        ]));
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new class implements RequestHandlerInterface {
            public bool $called = false;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new Response(200);
            }
        };
    }

    private function middleware(?int $maxBytes = null): CsrfMiddleware
    {
        return new CsrfMiddleware($this->tokens, $this->twig, $this->logger, $maxBytes);
    }

    private function post(string $path = '/login'): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('POST', 'http://crm.test' . $path);
    }

    public function testGetPassesWithoutToken(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://crm.test/');
        $response = $this->middleware()->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->handler->called);
    }

    public function testPostWithValidBodyTokenPasses(): void
    {
        $token = $this->tokens->getToken();
        $response = $this->middleware()->process($this->post()->withParsedBody(['_csrf' => $token]), $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->handler->called);
    }

    public function testPostWithValidHeaderTokenPasses(): void
    {
        $token = $this->tokens->getToken();
        $response = $this->middleware()->process($this->post()->withHeader('X-CSRF-Token', $token), $this->handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->handler->called);
    }

    public function testMissingTokenRendersHtml403WithBackLink(): void
    {
        $this->tokens->getToken();
        $request = $this->post()->withHeader('Referer', 'http://crm.test/login');
        $response = $this->middleware()->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Die Sitzung ist abgelaufen', $body);
        $this->assertStringContainsString('/login', $body);
        $this->assertFalse($this->handler->called);
    }

    public function testForeignRefererFallsBackToRoot(): void
    {
        $request = $this->post()->withHeader('Referer', 'https://evil.example/x');
        $response = $this->middleware()->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringEndsWith('|/', (string) $response->getBody());
    }

    public function testWrongTokenReturns403(): void
    {
        $this->tokens->getToken();
        $response = $this->middleware()->process($this->post()->withParsedBody(['_csrf' => 'falsch']), $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($this->handler->called);
    }

    public function testHeaderRequestGetsJsonError(): void
    {
        $this->tokens->getToken();
        $response = $this->middleware()->process($this->post()->withHeader('X-CSRF-Token', 'falsch'), $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"success":false,"error":"csrf"}', (string) $response->getBody());
    }

    public function testOversizedBodyReturns413(): void
    {
        $request = $this->post()->withHeader('Content-Length', '500');
        $response = $this->middleware(100)->process($request, $this->handler);

        $this->assertSame(413, $response->getStatusCode());
        $this->assertStringContainsString('zu gross', (string) $response->getBody());
        $this->assertFalse($this->handler->called);
    }

    public function testLogsWarningOnRejection(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $this->middleware()->process($this->post(), $this->handler);
    }
}
