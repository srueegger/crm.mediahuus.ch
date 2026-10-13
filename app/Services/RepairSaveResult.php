<?php

declare(strict_types=1);

namespace App\Services;

final class RepairSaveResult
{
    public function __construct(
        public readonly int $documentId,
        public readonly ?MailResult $mail
    ) {
    }

    public function mailFailed(): bool
    {
        return $this->mail !== null && !$this->mail->isSent();
    }
}
