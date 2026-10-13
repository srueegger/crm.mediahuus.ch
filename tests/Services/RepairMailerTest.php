<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Repositories\RepairEventRepository;
use App\Services\AppUrl;
use App\Services\MailResult;
use App\Services\MailService;
use App\Services\PdfService;
use App\Services\RepairMailer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RepairMailerTest extends TestCase
{
    private const TOKEN = 'abcdef0123456789abcdef0123456789';

    private MailService&MockObject $mail;
    private PdfService&MockObject $pdf;
    private RepairEventRepository&MockObject $events;
    private RepairMailer $mailer;

    protected function setUp(): void
    {
        $this->mail = $this->createMock(MailService::class);
        $this->pdf = $this->createMock(PdfService::class);
        $this->events = $this->createMock(RepairEventRepository::class);
        $twig = new Environment(new FilesystemLoader(__DIR__ . '/../../templates'));
        $this->mailer = new RepairMailer(
            $this->mail,
            $twig,
            $this->pdf,
            new AppUrl('https://crm.test'),
            $this->events,
            new NullLogger()
        );
    }

    private function document(?string $email = 'kunde@example.ch'): Document
    {
        return new Document(Document::TYPE_REPAIR, 'RP-2026-000001', 1, 1, 'Max Muster', '079 123 45 67', $email, 5);
    }

    private function repair(): Repair
    {
        return new Repair(
            7, 5, 'received', self::TOKEN, 'iPhone 13', '356938035643809', 'screen_damage',
            'Display gesprungen', null, '120.50', null, null, 'GEHEIM-4711', null
        );
    }

    private function branch(): Branch
    {
        return new Branch('Mediahuus Reinach', 'Hauptstrasse 1', '5734', 'Reinach', '062 123 45 67', 'reinach@mediahuus.ch');
    }

    public function testNoCustomerEmailSkipsSilently(): void
    {
        $this->mail->expects($this->never())->method('send');
        $this->events->expects($this->never())->method('add');
        $this->pdf->expects($this->never())->method('generateRepairPdf');

        $this->assertNull($this->mailer->sendCreated($this->document(null), $this->repair(), $this->branch(), 1));
        $this->assertNull($this->mailer->sendReady($this->document(''), $this->repair(), $this->branch(), 1));
    }

    public function testCreatedMailHasLinkSubjectAndPdf(): void
    {
        $this->pdf->method('generateRepairPdf')->willReturn('%PDF-fake');
        $this->pdf->method('getRepairPdfFilename')->willReturn('Reparatur_RP-2026-000001.pdf');
        $sent = null;
        $this->mail->method('send')->willReturnCallback(function (Email $e) use (&$sent) {
            $sent = $e;
            return MailResult::sent();
        });

        $result = $this->mailer->sendCreated($this->document(), $this->repair(), $this->branch(), 1);

        $this->assertTrue($result->isSent());
        $this->assertSame('kunde@example.ch', $sent->getTo()[0]->getAddress());
        $this->assertSame('Ihre Reparatur RP-2026-000001 bei Mediahuus', $sent->getSubject());
        $this->assertSame('reinach@mediahuus.ch', $sent->getReplyTo()[0]->getAddress());
        $link = 'https://crm.test/r/' . self::TOKEN;
        $this->assertStringContainsString($link, $sent->getHtmlBody());
        $this->assertStringContainsString($link, $sent->getTextBody());
        $this->assertStringContainsString('iPhone 13', $sent->getTextBody());
        $this->assertCount(1, $sent->getAttachments());
        $part = $sent->getAttachments()[0];
        $this->assertSame('Reparatur_RP-2026-000001.pdf', $part->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename'));
        $this->assertSame('application/pdf', $part->getMediaType() . '/' . $part->getMediaSubtype());
    }

    public function testReadyMailSubject(): void
    {
        $sent = null;
        $this->mail->method('send')->willReturnCallback(function (Email $e) use (&$sent) {
            $sent = $e;
            return MailResult::sent();
        });

        $this->mailer->sendReady($this->document(), $this->repair(), $this->branch(), 1);

        $this->assertSame('Ihr Gerät ist abholbereit (RP-2026-000001)', $sent->getSubject());
        $this->assertCount(0, $sent->getAttachments());
        $this->assertStringContainsString('CHF 120.50', $sent->getTextBody());
        $this->assertStringContainsString('https://crm.test/r/' . self::TOKEN, $sent->getHtmlBody());
    }

    public function testUnlockCodeAndImeiNeverInMail(): void
    {
        $this->pdf->method('generateRepairPdf')->willReturn('%PDF-fake');
        $this->pdf->method('getRepairPdfFilename')->willReturn('x.pdf');
        $bodies = [];
        $this->mail->method('send')->willReturnCallback(function (Email $e) use (&$bodies) {
            $bodies[] = $e->getHtmlBody() . $e->getTextBody();
            return MailResult::sent();
        });

        $this->mailer->sendCreated($this->document(), $this->repair(), $this->branch(), 1);
        $this->mailer->sendReady($this->document(), $this->repair(), $this->branch(), 1);

        $this->assertCount(2, $bodies);
        foreach ($bodies as $body) {
            $this->assertStringNotContainsString('GEHEIM-4711', $body);
            $this->assertStringNotContainsString('356938035643809', $body);
        }
    }

    public function testSuccessWritesSentEvent(): void
    {
        $this->pdf->method('generateRepairPdf')->willReturn('%PDF-fake');
        $this->pdf->method('getRepairPdfFilename')->willReturn('x.pdf');
        $this->mail->method('send')->willReturn(MailResult::sent());
        $captured = null;
        $this->events->expects($this->once())->method('add')->willReturnCallback(function (RepairEvent $e) use (&$captured) {
            $captured = $e;
        });

        $this->mailer->sendCreated($this->document(), $this->repair(), $this->branch(), 3);

        $this->assertSame('email_sent', $captured->getEventType());
        $this->assertSame('Erfassungs-E-Mail an kunde@example.ch', $captured->getMessage());
        $this->assertSame(7, $captured->getRepairId());
        $this->assertSame(3, $captured->getUserId());
    }

    public function testNotConfiguredWritesFailedEvent(): void
    {
        $this->mail->method('send')->willReturn(MailResult::notConfigured());
        $captured = null;
        $this->events->expects($this->once())->method('add')->willReturnCallback(function (RepairEvent $e) use (&$captured) {
            $captured = $e;
        });

        $result = $this->mailer->sendReady($this->document(), $this->repair(), $this->branch(), null);

        $this->assertSame(MailResult::NOT_CONFIGURED, $result->status);
        $this->assertSame('email_failed', $captured->getEventType());
        $this->assertStringContainsString('Mailversand nicht konfiguriert', $captured->getMessage());
        $this->assertStringContainsString('Abholbereit-E-Mail an kunde@example.ch', $captured->getMessage());
    }

    public function testFailedWritesReason(): void
    {
        $this->mail->method('send')->willReturn(MailResult::failed('Timeout'));
        $captured = null;
        $this->events->method('add')->willReturnCallback(function (RepairEvent $e) use (&$captured) {
            $captured = $e;
        });

        $this->mailer->sendReady($this->document(), $this->repair(), $this->branch(), 1);

        $this->assertSame('email_failed', $captured->getEventType());
        $this->assertStringContainsString('fehlgeschlagen: Timeout', $captured->getMessage());
    }

    public function testPdfFailureWritesFailedEventAndDoesNotThrow(): void
    {
        $this->pdf->method('generateRepairPdf')->willThrowException(new \RuntimeException('PDF kaputt'));
        $this->mail->expects($this->never())->method('send');
        $captured = null;
        $this->events->expects($this->once())->method('add')->willReturnCallback(function (RepairEvent $e) use (&$captured) {
            $captured = $e;
        });

        $result = $this->mailer->sendCreated($this->document(), $this->repair(), $this->branch(), 1);

        $this->assertSame(MailResult::FAILED, $result->status);
        $this->assertSame('email_failed', $captured->getEventType());
        $this->assertStringContainsString('PDF kaputt', $captured->getMessage());
    }

    public function testDeviceNameIsEscapedInHtml(): void
    {
        $repair = new Repair(
            7, 5, 'received', self::TOKEN, '<script>alert(1)</script>', null, null,
            'x', null, null, null, null, null, null
        );
        $sent = null;
        $this->mail->method('send')->willReturnCallback(function (Email $e) use (&$sent) {
            $sent = $e;
            return MailResult::sent();
        });

        $this->mailer->sendReady($this->document(), $repair, $this->branch(), 1);

        $this->assertStringNotContainsString('<script>', $sent->getHtmlBody());
        $this->assertStringContainsString('&lt;script&gt;', $sent->getHtmlBody());
    }

    public function testNoPriceLineWhenPriceIsNull(): void
    {
        $repair = new Repair(
            7, 5, 'received', self::TOKEN, 'iPhone 13', null, null,
            'x', null, null, null, null, null, null
        );
        $sent = null;
        $this->mail->method('send')->willReturnCallback(function (Email $e) use (&$sent) {
            $sent = $e;
            return MailResult::sent();
        });

        $this->mailer->sendReady($this->document(), $repair, $this->branch(), 1);

        $this->assertStringNotContainsString('CHF', $sent->getTextBody());
        $this->assertStringNotContainsString('CHF', $sent->getHtmlBody());
        $this->assertDoesNotMatchRegularExpression("/\n{3,}/", $sent->getTextBody());
    }
}
