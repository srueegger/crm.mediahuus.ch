<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\Repair;
use PHPUnit\Framework\TestCase;

final class RepairTest extends TestCase
{
    private function make(string $status = Repair::STATUS_RECEIVED, ?\DateTimeImmutable $due = null): Repair
    {
        return new Repair(
            id: 1,
            documentId: 10,
            status: $status,
            statusToken: str_repeat('a', 32),
            deviceName: 'iPhone 13',
            imeiSerial: null,
            damageType: 'display',
            issueText: 'Display gebrochen',
            workText: null,
            priceChf: '129.00',
            dueDate: $due,
            accessories: null,
            unlockCode: '1234',
            closedAt: null
        );
    }

    public function testNewRepairIsOpen(): void
    {
        $repair = $this->make();
        $this->assertTrue($repair->isOpen());
        $this->assertSame('Angenommen', $repair->statusLabel());
    }

    public function testPickedUpClearsUnlockCodeAndSetsClosedAt(): void
    {
        $now = new \DateTimeImmutable('2026-10-13 10:00:00');
        $closed = $this->make()->withStatus(Repair::STATUS_PICKED_UP, $now);
        $this->assertNull($closed->getUnlockCode());
        $this->assertEquals($now, $closed->getClosedAt());
        $this->assertFalse($closed->isOpen());
    }

    public function testCancelledClearsUnlockCode(): void
    {
        $closed = $this->make()->withStatus(Repair::STATUS_CANCELLED, new \DateTimeImmutable());
        $this->assertNull($closed->getUnlockCode());
        $this->assertFalse($closed->isOpen());
    }

    public function testReopenClearsClosedAt(): void
    {
        $now = new \DateTimeImmutable('2026-10-13 10:00:00');
        $reopened = $this->make()
            ->withStatus(Repair::STATUS_PICKED_UP, $now)
            ->withStatus(Repair::STATUS_IN_PROGRESS, $now);
        $this->assertNull($reopened->getClosedAt());
        $this->assertNull($reopened->getUnlockCode());
        $this->assertTrue($reopened->isOpen());
    }

    public function testInvalidStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->make()->withStatus('done', new \DateTimeImmutable());
    }

    public function testOverdueOnlyWhenOpenAndPastDue(): void
    {
        $yesterday = new \DateTimeImmutable('yesterday');
        $open = $this->make(Repair::STATUS_RECEIVED, $yesterday);
        $this->assertTrue($open->toArray()['is_overdue']);
        $this->assertSame($yesterday->format('Y-m-d'), $open->toArray()['due_date']);

        $closed = $open->withStatus(Repair::STATUS_PICKED_UP, new \DateTimeImmutable());
        $this->assertFalse($closed->toArray()['is_overdue']);
    }
}
