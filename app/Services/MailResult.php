<?php

declare(strict_types=1);

namespace App\Services;

final class MailResult
{
    public const SENT = 'sent';
    public const NOT_CONFIGURED = 'not_configured';
    public const FAILED = 'failed';

    private function __construct(
        public readonly string $status,
        public readonly ?string $error = null
    ) {
    }

    public static function sent(): self
    {
        return new self(self::SENT);
    }

    public static function notConfigured(): self
    {
        return new self(self::NOT_CONFIGURED);
    }

    public static function failed(string $error): self
    {
        return new self(self::FAILED, $error);
    }

    public function isSent(): bool
    {
        return $this->status === self::SENT;
    }
}
