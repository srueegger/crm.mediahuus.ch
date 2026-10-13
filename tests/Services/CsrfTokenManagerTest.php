<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\CsrfTokenManager;
use PHPUnit\Framework\TestCase;

class CsrfTokenManagerTest extends TestCase
{
    private CsrfTokenManager $manager;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->manager = new CsrfTokenManager();
    }

    public function testGeneratesHexToken(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $this->manager->getToken());
    }

    public function testTokenStableAcrossCalls(): void
    {
        $this->assertSame($this->manager->getToken(), (new CsrfTokenManager())->getToken());
    }

    public function testValidToken(): void
    {
        $this->assertTrue($this->manager->isValid($this->manager->getToken()));
    }

    public function testWrongOrEmptyTokenInvalid(): void
    {
        $this->manager->getToken();

        $this->assertFalse($this->manager->isValid(str_repeat('a', 64)));
        $this->assertFalse($this->manager->isValid(''));
        $this->assertFalse($this->manager->isValid(null));
    }

    public function testNonStringIsInvalid(): void
    {
        $this->manager->getToken();

        $this->assertFalse($this->manager->isValid(['x']));
    }

    public function testInvalidWhenNoTokenInSession(): void
    {
        $this->assertFalse($this->manager->isValid('abc'));
        $this->assertArrayNotHasKey('_csrf_token', $_SESSION);
    }

    public function testRegenerateChangesToken(): void
    {
        $old = $this->manager->getToken();

        $this->manager->regenerate();

        $this->assertNotSame($old, $this->manager->getToken());
    }
}
