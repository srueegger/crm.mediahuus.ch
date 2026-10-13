<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Zentraler E-Mail-Versand. Jede E-Mail des Systems läuft über send().
 * Wirft nie in den aufrufenden Code.
 */
class MailService
{
    public function __construct(
        private ?TransportInterface $transport,
        private Address $from,
        private LoggerInterface $logger
    ) {
    }

    public function send(Email $email): MailResult
    {
        if ($this->transport === null) {
            $this->logger->info('E-Mail nicht versendet: MAILER_DSN nicht konfiguriert', [
                'subject' => $email->getSubject(),
            ]);
            return MailResult::notConfigured();
        }

        try {
            $email->from($this->from);
            $this->transport->send($email);
            return MailResult::sent();
        } catch (\Throwable $e) {
            $this->logger->error('E-Mail-Versand fehlgeschlagen: ' . $e->getMessage(), [
                'subject' => $email->getSubject(),
            ]);
            return MailResult::failed($e->getMessage());
        }
    }
}
