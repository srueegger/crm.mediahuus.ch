<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\InvoiceCalculator;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    private InvoiceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvoiceCalculator();
    }

    /**
     * Hinweis: PHP wandelt numerische String-Schluessel wie '0' in int 0 um;
     * '8.1' und '2.6' bleiben Strings. Daher wird auf Werte geprueft.
     */
    private function mixedItems(): array
    {
        return [
            ['quantity' => '2', 'unit_price_gross' => '49.90', 'vat_rate' => '8.1'],
            ['quantity' => '1', 'unit_price_gross' => '20.00', 'vat_rate' => '2.6'],
            ['quantity' => '1', 'unit_price_gross' => '10.00', 'vat_rate' => '0'],
        ];
    }

    public function testSingleLineStandardRate(): void
    {
        $r = $this->calculator->calculate([
            ['quantity' => '1', 'unit_price_gross' => '108.10', 'vat_rate' => '8.1'],
        ]);

        $this->assertSame([['line_total_gross' => '108.10']], $r['lines']);
        $this->assertSame(['gross' => '108.10', 'vat' => '8.10', 'net' => '100.00'], $r['vat_groups']['8.1']);
        $this->assertSame('100.00', $r['total_net']);
        $this->assertSame('8.10', $r['total_vat']);
        $this->assertSame('108.10', $r['total_gross']);
    }

    public function testQuantityWithDecimals(): void
    {
        $r = $this->calculator->calculate([
            ['quantity' => '1.5', 'unit_price_gross' => '100.00', 'vat_rate' => '8.1'],
        ]);

        $this->assertSame('150.00', $r['lines'][0]['line_total_gross']);
        $this->assertSame(['gross' => '150.00', 'vat' => '11.24', 'net' => '138.76'], $r['vat_groups']['8.1']);
        $this->assertSame('138.76', $r['total_net']);
    }

    public function testMixedRates(): void
    {
        $r = $this->calculator->calculate($this->mixedItems());

        $this->assertSame(['8.1', '2.6', 0], array_keys($r['vat_groups']));
        $this->assertSame(['gross' => '99.80', 'vat' => '7.48', 'net' => '92.32'], $r['vat_groups']['8.1']);
        $this->assertSame(['gross' => '20.00', 'vat' => '0.51', 'net' => '19.49'], $r['vat_groups']['2.6']);
        $this->assertSame(['gross' => '10.00', 'vat' => '0.00', 'net' => '10.00'], $r['vat_groups'][0]);
        $this->assertSame('129.80', $r['total_gross']);
        $this->assertSame('7.99', $r['total_vat']);
        $this->assertSame('121.81', $r['total_net']);
    }

    public function testTotalsAreConsistent(): void
    {
        $r = $this->calculator->calculate($this->mixedItems());

        $this->assertSame($r['total_gross'], bcadd($r['total_net'], $r['total_vat'], 2));
    }

    public function testSmallAmountsRounding(): void
    {
        $r = $this->calculator->calculate([
            ['quantity' => '1', 'unit_price_gross' => '0.05', 'vat_rate' => '8.1'],
        ]);
        $this->assertSame(['gross' => '0.05', 'vat' => '0.00', 'net' => '0.05'], $r['vat_groups']['8.1']);

        $r = $this->calculator->calculate([
            ['quantity' => '3', 'unit_price_gross' => '0.35', 'vat_rate' => '8.1'],
        ]);
        $this->assertSame('1.05', $r['lines'][0]['line_total_gross']);
        $this->assertSame('0.08', $r['vat_groups']['8.1']['vat']);
    }

    public function testLineTotalRounding(): void
    {
        $this->assertSame('3.33', $this->calculator->lineTotal('0.333', '10.00'));
        $this->assertSame('3.35', $this->calculator->lineTotal('0.335', '10.00'));
        $this->assertSame('-3.35', $this->calculator->lineTotal('-0.335', '10.00'));
    }

    public function testEmptyItems(): void
    {
        $r = $this->calculator->calculate([]);

        $this->assertSame([], $r['lines']);
        $this->assertSame([], $r['vat_groups']);
        $this->assertSame('0.00', $r['total_gross']);
    }
}
