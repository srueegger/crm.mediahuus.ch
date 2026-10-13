<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\MailResult;
use App\Services\MailService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailServiceTest extends TestCase
{
    private function email(): Email
    {
        return (new Email())
            ->to('kunde@example.com')
            ->subject('Test')
            ->text('Hallo');
    }

    public function testWithoutTransportReturnsNotConfiguredAndLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $service = new MailService(null, new Address('info@mediahuus.ch', 'Mediahuus'), $logger);
        $result = $service->send($this->email());

        $this->assertSame(MailResult::NOT_CONFIGURED, $result->status);
        $this->assertFalse($result->isSent());
    }

    public function testSendsWithConfiguredFromAddress(): void
    {
        $transport = new class implements TransportInterface {
            public ?RawMessage $message = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->message = $message;
                return null;
            }

            public function __toString(): string
            {
                return 'stub://';
            }
        };

        $service = new MailService(
            $transport,
            new Address('info@mediahuus.ch', 'Mediahuus'),
            $this->createMock(LoggerInterface::class)
        );
        $result = $service->send($this->email());

        $this->assertTrue($result->isSent());
        $this->assertNull($result->error);
        $this->assertInstanceOf(Email::class, $transport->message);
        $from = $transport->message->getFrom();
        $this->assertSame('info@mediahuus.ch', $from[0]->getAddress());
        $this->assertSame('Mediahuus', $from[0]->getName());
    }

    public function testTransportExceptionReturnsFailed(): void
    {
        $transport = new class implements TransportInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('boom');
            }

            public function __toString(): string
            {
                return 'stub://';
            }
        };

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $service = new MailService($transport, new Address('info@mediahuus.ch', 'Mediahuus'), $logger);
        $result = $service->send($this->email());

        $this->assertSame(MailResult::FAILED, $result->status);
        $this->assertSame('boom', $result->error);
    }
}
