<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Services\PdfService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RepairPdfTest extends TestCase
{
    private function document(): Document
    {
        return new Document(Document::TYPE_REPAIR, 'RE-2026-000001', 1, 1, 'Max Muster', '079 123 45 67', 'max@example.ch', 5);
    }

    private function repair(bool $full = true): Repair
    {
        return new Repair(
            1, 5, 'received', str_repeat('a', 32), 'iPhone 13',
            $full ? '356938035643809' : null,
            'screen_damage',
            'Display gesprungen',
            $full ? 'Display ersetzen' : null,
            $full ? '189.50' : null,
            $full ? new \DateTimeImmutable('2026-10-20') : null,
            $full ? 'Hülle, Ladekabel' : null,
            'GEHEIM-4711',
            null
        );
    }

    private function branch(): Branch
    {
        return new Branch('Mediahuus Reinach', 'Hauptstrasse 1', '5734', 'Reinach', '062 123 45 67', 'info@mediahuus.ch');
    }

    public function testGeneratesPdf(): void
    {
        $service = new PdfService(new NullLogger());
        $pdf = $service->generateRepairPdf($this->document(), $this->repair(), $this->branch(), 'https://crm.mediahuus.ch/r/' . str_repeat('a', 32));
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function testGeneratesPdfWithoutOptionalFields(): void
    {
        $service = new PdfService(new NullLogger());
        $pdf = $service->generateRepairPdf($this->document(), $this->repair(false), $this->branch(), 'https://crm.mediahuus.ch/r/x');
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function testLinesNeverContainUnlockCode(): void
    {
        $service = new PdfService(new NullLogger());
        foreach ($service->repairPdfLines($this->document(), $this->repair()) as $line) {
            $this->assertStringNotContainsString('GEHEIM-4711', $line['value']);
            $this->assertStringNotContainsString('GEHEIM-4711', $line['label']);
        }
    }

    public function testLinesOmitEmptyOptionalFields(): void
    {
        $service = new PdfService(new NullLogger());
        $labels = array_column($service->repairPdfLines($this->document(), $this->repair(false)), 'label');
        foreach (['IMEI / Seriennummer', 'Geplante Arbeiten', 'Zubehör', 'Voraussichtlicher Preis', 'Fertig bis'] as $label) {
            $this->assertNotContains($label, $labels);
        }
        $this->assertContains('Gerät', $labels);
        $this->assertContains('Fehlerbeschreibung', $labels);
    }

    public function testLinesContainFormattedValues(): void
    {
        $service = new PdfService(new NullLogger());
        $map = array_column($service->repairPdfLines($this->document(), $this->repair()), 'value', 'label');
        $this->assertSame('CHF 189.50', $map['Voraussichtlicher Preis']);
        $this->assertSame('20.10.2026', $map['Fertig bis']);
    }
}
