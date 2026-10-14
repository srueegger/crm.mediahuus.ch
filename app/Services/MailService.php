<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Zentraler E-Mail-Versand. Jede E-Mail des Systems läuft über send().
 * Wirft nie in den aufrufenden Code.
 */
class MailService
{
    private const DEFAULT_FROM = 'noreply@crm.mediahuus.ch';
    private const DEFAULT_FROM_NAME = 'Mediahuus';

    public function __construct(
        private ?TransportInterface $transport,
        private Address $from,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Baut den Service aus den Umgebungsvariablen. Ungültige Konfiguration
     * wirft nie: Fehler werden geloggt (ungültiger DSN = kein Versand,
     * ungültige Absenderadresse = Standardabsender).
     *
     * @param array<string, mixed> $env
     */
    public static function fromEnv(array $env, LoggerInterface $logger): self
    {
        $transport = null;
        $dsn = trim((string) ($env['MAILER_DSN'] ?? ''));
        if ($dsn !== '') {
            try {
                $transport = Transport::fromDsn($dsn);
            } catch (\Throwable $e) {
                $logger->error('MAILER_DSN ungültig, E-Mail-Versand deaktiviert: ' . $e->getMessage());
            }
        }

        $address = trim((string) ($env['MAIL_FROM'] ?? '')) ?: self::DEFAULT_FROM;
        $name = trim((string) ($env['MAIL_FROM_NAME'] ?? '')) ?: self::DEFAULT_FROM_NAME;
        try {
            $from = new Address($address, $name);
        } catch (\Throwable $e) {
            $logger->error('MAIL_FROM ungültig, Standardabsender wird verwendet: ' . $e->getMessage());
            $from = new Address(self::DEFAULT_FROM, self::DEFAULT_FROM_NAME);
        }

        return new self($transport, $from, $logger);
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
