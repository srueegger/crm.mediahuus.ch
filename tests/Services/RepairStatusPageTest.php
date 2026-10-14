<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Services\RepairStatusPage;
use PHPUnit\Framework\TestCase;

class RepairStatusPageTest extends TestCase
{
    private function document(): Document
    {
        return new Document(Document::TYPE_REPAIR, 'RP-2026-000001', 1, 1, 'Erika Muster', '079 555 44 33', 'erika@example.ch', 5);
    }

    private function repair(string $status = 'received', ?string $price = '120.5', ?\DateTimeImmutable $due = null, ?\DateTimeImmutable $closedAt = null): Repair
    {
        return new Repair(
            1, 5, $status, str_repeat('a', 32), 'iPhone 13', '356938035643809', 'screen_damage',
            'Display gesprungen', 'interne Arbeit', $price, $due, 'Hülle', 'GEHEIM-4711', $closedAt
        );
    }

    private function branch(): Branch
    {
        return new Branch('Mediahuus Reinach', 'Hauptstrasse 1', '5734', 'Reinach', '062 123 45 67', 'info@mediahuus.ch');
    }

    private function events(): array
    {
        return [
            new RepairEvent(1, 1, RepairEvent::TYPE_STATUS, 'received', 'intern', 1, new \DateTimeImmutable('2026-10-13 09:05')),
            new RepairEvent(2, 1, RepairEvent::TYPE_EMAIL_SENT, null, 'mail an erika@example.ch', 1, new \DateTimeImmutable('2026-10-13 09:06')),
        ];
    }

    public function testContainsOnlyAllowedKeys(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair(), $this->events(), $this->branch());
        $expected = ['doc_number', 'device_name', 'status', 'status_label', 'cancelled', 'steps', 'history', 'due_date', 'price_chf', 'branch'];
        $keys = array_keys($data);
        sort($keys);
        sort($expected);
        $this->assertSame($expected, $keys);
        $this->assertSame(['name', 'street', 'zip', 'city', 'phone', 'email'], array_keys($data['branch']));
    }

    public function testNoPersonalDataAnywhere(): void
    {
        $json = json_encode((new RepairStatusPage())->build($this->document(), $this->repair(), $this->events(), $this->branch()));
        foreach (['Erika Muster', '079 555 44 33', 'erika@example.ch', '356938035643809', 'GEHEIM-4711'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function testHistoryOnlyStatusEventsWithoutNotes(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair(), $this->events(), $this->branch());
        $this->assertCount(1, $data['history']);
        $this->assertSame(['status_label' => 'Angenommen', 'date' => '13.10.2026 09:05'], $data['history'][0]);
        $this->assertStringNotContainsString('intern', json_encode($data));
    }

    public function testOmitsEmptyPriceAndDueDate(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair('received', null, null), [], $this->branch());
        $this->assertNull($data['price_chf']);
        $this->assertNull($data['due_date']);
    }

    public function testFormatsPriceAndDueDate(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair('received', '120.5', new \DateTimeImmutable('2026-10-20')), [], $this->branch());
        $this->assertSame('CHF 120.50', $data['price_chf']);
        $this->assertSame('20.10.2026', $data['due_date']);
    }

    public function testStepsMarkCurrent(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair('waiting_parts'), [], $this->branch());
        $this->assertCount(5, $data['steps']);
        $this->assertTrue($data['steps'][2]['current']);
        $this->assertTrue($data['steps'][0]['done']);
        $this->assertTrue($data['steps'][1]['done']);
        $this->assertFalse($data['steps'][3]['done']);
        $this->assertFalse($data['steps'][0]['current']);
    }

    public function testCancelledFlag(): void
    {
        $data = (new RepairStatusPage())->build($this->document(), $this->repair('cancelled'), [], $this->branch());
        $this->assertTrue($data['cancelled']);
        $data = (new RepairStatusPage())->build($this->document(), $this->repair('ready'), [], $this->branch());
        $this->assertFalse($data['cancelled']);
    }

    public function testExpiredAfter30Days(): void
    {
        $now = new \DateTimeImmutable('2026-11-20 12:00');
        $page = new RepairStatusPage();
        $this->assertTrue($page->isExpired($this->repair('picked_up', null, null, $now->modify('-31 days')), $now));
        $this->assertFalse($page->isExpired($this->repair('picked_up', null, null, $now->modify('-29 days')), $now));
        $this->assertFalse($page->isExpired($this->repair(), $now));
    }
}
