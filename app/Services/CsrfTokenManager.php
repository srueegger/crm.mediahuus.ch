<?php

declare(strict_types=1);

namespace App\Services;

class CsrfTokenManager
{
    private const SESSION_KEY = '_csrf_token';

    public function getToken(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $this->regenerate();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function isValid(mixed $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($expected) && hash_equals($expected, $token);
    }

    public function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
