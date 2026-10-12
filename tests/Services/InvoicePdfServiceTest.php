<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoicePdfService;
use App\Services\QrBillFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class InvoicePdfServiceTest extends TestCase
{
    private InvoicePdfService $service;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../../config/invoice.php';
        $this->service = new class ($config, new QrBillFactory($config['creditor']), new NullLogger()) extends InvoicePdfService {
            protected function createPdf(): \TCPDF
            {
                $pdf = parent::createPdf();
                $pdf->setCompression(false);

                return $pdf;
            }
        };
    }

    private function document(): Document
    {
        return new Document(
            Document::TYPE_INVOICE,
            'RE-2026-10-12-01',
            2,
            1,
            'Müller & Söhne AG',
            null,
            null,
            1,
            null,
            null,
            'Musterweg 3a',
            '8001',
            'Zürich'
        );
    }

    private function branch(): Branch
    {
        return new Branch('Mediahuus Reinach', 'Hauptstrasse 38', '4153', 'Reinach', '061 711 11 11', 'reinach@mediahuus.ch', 2);
    }

    private function invoice(int $itemCount): Invoice
    {
        $items = [];
        $rates = ['8.1', '2.6', '0'];
        for ($i = 1; $i <= $itemCount; $i++) {
            $items[] = new InvoiceItem($i, 'Display-Reparatur iPhone ' . $i, $i === 2 ? '1.50' : '2.00', '1234.50', $rates[$i % 3], '2469.00');
        }

        return new Invoice(
            1,
            'Müller & Söhne AG',
            'Hans Müller',
            new \DateTimeImmutable('2026-10-12'),
            10,
            new \DateTimeImmutable('2026-10-22'),
            '2283.33',
            '185.67',
            '2469.00',
            'RF000000',
            $items
        );
    }

    public function testGeneratesPdf(): void
    {
        $pdf = $this->service->generate($this->document(), $this->invoice(3), $this->branch());
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function testSinglePageWhenFewItems(): void
    {
        $pdf = $this->service->generate($this->document(), $this->invoice(3), $this->branch());
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page[^s]/', $pdf));
    }

    public function testManyItemsSpanPages(): void
    {
        $pdf = $this->service->generate($this->document(), $this->invoice(40), $this->branch());
        $this->assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page[^s]/', $pdf));
    }

    public function testNeedsSeparateQrPage(): void
    {
        $this->assertFalse($this->service->needsSeparateQrPage(192.0));
        $this->assertTrue($this->service->needsSeparateQrPage(192.1));
    }

    public function testFilename(): void
    {
        $this->assertSame('Rechnung_RE-2026-10-12-01.pdf', $this->service->getFilename($this->document()));
    }

    public function testHeaderUsesConfiguredEmailNotBranchEmail(): void
    {
        $pdf = $this->service->generate($this->document(), $this->invoice(3), $this->branch());
        $this->assertStringContainsString('info@mediahuus.ch', $pdf);
        $this->assertStringNotContainsString('reinach@mediahuus.ch', $pdf);
        $this->assertStringContainsString('061 711 11 11', $pdf);
    }
}
