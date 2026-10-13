<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Repositories\RepairEventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Baut die Reparatur-E-Mails und übergibt sie dem MailService.
 * Das Ergebnis wird als Ereignis im Reparaturverlauf protokolliert.
 */
class RepairMailer
{
    public function __construct(
        private MailService $mail,
        private Environment $twig,
        private PdfService $pdf,
        private AppUrl $url,
        private RepairEventRepository $events,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Erfassungs-E-Mail mit Status-Link und Auftragsbestätigung (PDF).
     * Null, wenn der Kunde keine E-Mail-Adresse hat.
     */
    public function sendCreated(Document $document, Repair $repair, Branch $branch, ?int $userId): ?MailResult
    {
        $to = $this->recipient($document);
        if ($to === null) {
            return null;
        }

        $statusUrl = $this->url->repairStatus($repair->getStatusToken());
        $context = $this->context($document, $repair, $branch, $statusUrl);

        $email = $this->buildEmail(
            $to,
            'Ihre Reparatur ' . $document->getDocNumber() . ' bei Mediahuus',
            'repair_created',
            $context,
            $branch
        );
        $email->attach(
            $this->pdf->generateRepairPdf($document, $repair, $branch, $statusUrl),
            $this->pdf->getRepairPdfFilename($document),
            'application/pdf'
        );

        return $this->deliver($email, $repair, $userId, 'Erfassungs-E-Mail an ' . $to);
    }

    /**
     * «Abholbereit»-E-Mail. Null, wenn der Kunde keine E-Mail-Adresse hat.
     */
    public function sendReady(Document $document, Repair $repair, Branch $branch, ?int $userId): ?MailResult
    {
        $to = $this->recipient($document);
        if ($to === null) {
            return null;
        }

        $context = $this->context(
            $document,
            $repair,
            $branch,
            $this->url->repairStatus($repair->getStatusToken())
        );
        $context['price_chf'] = $repair->getPriceChf() !== null
            ? number_format((float) $repair->getPriceChf(), 2, '.', "'")
            : null;

        $email = $this->buildEmail(
            $to,
            'Ihr Gerät ist abholbereit (' . $document->getDocNumber() . ')',
            'repair_ready',
            $context,
            $branch
        );

        return $this->deliver($email, $repair, $userId, 'Abholbereit-E-Mail an ' . $to);
    }

    private function recipient(Document $document): ?string
    {
        $email = trim((string) $document->getCustomerEmail());
        return $email !== '' ? $email : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Document $document, Repair $repair, Branch $branch, string $statusUrl): array
    {
        return [
            'doc_number' => $document->getDocNumber(),
            'device_name' => $repair->getDeviceName(),
            'status_url' => $statusUrl,
            'branch' => [
                'name' => $branch->getName(),
                'street' => $branch->getStreet(),
                'zip' => $branch->getZip(),
                'city' => $branch->getCity(),
                'phone' => $branch->getPhone(),
                'email' => $branch->getEmail(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildEmail(string $to, string $subject, string $template, array $context, Branch $branch): Email
    {
        $email = (new Email())
            ->to($to)
            ->subject($subject)
            ->html($this->twig->render("emails/{$template}.html.twig", $context))
            ->text($this->twig->render("emails/{$template}.txt.twig", $context));

        $replyTo = trim((string) $branch->getEmail());
        if ($replyTo !== '') {
            try {
                $email->replyTo($replyTo);
            } catch (\Throwable $e) {
                $this->logger->warning('Ungültige Filial-E-Mail für Reply-To: ' . $e->getMessage());
            }
        }

        return $email;
    }

    private function deliver(Email $email, Repair $repair, ?int $userId, string $label): MailResult
    {
        $result = $this->mail->send($email);

        if ($result->isSent()) {
            $this->logEvent($repair, $userId, RepairEvent::TYPE_EMAIL_SENT, $label);
        } else {
            $reason = $result->status === MailResult::NOT_CONFIGURED
                ? 'Mailversand nicht konfiguriert'
                : (string) $result->error;
            $this->logEvent($repair, $userId, RepairEvent::TYPE_EMAIL_FAILED, $label . ' fehlgeschlagen: ' . $reason);
        }

        return $result;
    }

    private function logEvent(Repair $repair, ?int $userId, string $type, string $message): void
    {
        try {
            $this->events->add(new RepairEvent(null, (int) $repair->getId(), $type, null, $message, $userId));
        } catch (\Throwable $e) {
            $this->logger->error('Reparatur-Ereignis konnte nicht gespeichert werden: ' . $e->getMessage());
        }
    }
}
