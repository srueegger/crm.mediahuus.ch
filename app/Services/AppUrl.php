<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Baut absolute URLs der Anwendung (z. B. für QR-Codes und E-Mails).
 */
class AppUrl
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function absolute(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function repairStatus(string $token): string
    {
        return $this->absolute('/r/' . $token);
    }
}
