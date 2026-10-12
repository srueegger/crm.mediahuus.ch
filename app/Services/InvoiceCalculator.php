<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Berechnet Rechnungsbetraege. Preise werden inkl. MWST erfasst.
 *
 * Alle Betraege sind Dezimalstrings im Format "0.00" (bcmath, keine Floats).
 * Hinweis zu vat_groups: PHP wandelt numerische String-Schluessel wie '0'
 * in int 0 um; '8.1' und '2.6' bleiben Strings. Die Gruppen sind absteigend
 * nach Satz sortiert.
 */
class InvoiceCalculator
{
    private const SCALE = 10;

    public function lineTotal(string $quantity, string $unitPriceGross): string
    {
        return $this->round2(bcmul($quantity, $unitPriceGross, self::SCALE));
    }

    /**
     * @param array<int, array{quantity: string, unit_price_gross: string, vat_rate: string}> $items
     * @return array{lines: array<int, array{line_total_gross: string}>, vat_groups: array<string|int, array{gross: string, vat: string, net: string}>, total_net: string, total_vat: string, total_gross: string}
     */
    public function calculate(array $items): array
    {
        $lines = [];
        $grossByRate = [];

        foreach ($items as $item) {
            $total = $this->lineTotal($item['quantity'], $item['unit_price_gross']);
            $lines[] = ['line_total_gross' => $total];

            $rate = $item['vat_rate'];
            $grossByRate[$rate] = bcadd($grossByRate[$rate] ?? '0', $total, 2);
        }

        // Absteigend nach Satz (numerisch vergleichen, Schluessel bleiben erhalten)
        uksort($grossByRate, fn($a, $b) => bccomp((string) $b, (string) $a, 4));

        $groups = [];
        $totalNet = '0.00';
        $totalVat = '0.00';
        $totalGross = '0.00';

        foreach ($grossByRate as $rate => $gross) {
            $rate = (string) $rate;
            // MWST pro Satz aus der Brutto-Gruppensumme: Brutto * Satz / (100 + Satz)
            $vat = $this->round2(bcdiv(bcmul($gross, $rate, self::SCALE), bcadd('100', $rate, self::SCALE), self::SCALE));
            $net = bcsub($gross, $vat, 2);

            $groups[$rate] = ['gross' => $gross, 'vat' => $vat, 'net' => $net];
            $totalNet = bcadd($totalNet, $net, 2);
            $totalVat = bcadd($totalVat, $vat, 2);
            $totalGross = bcadd($totalGross, $gross, 2);
        }

        return [
            'lines' => $lines,
            'vat_groups' => $groups,
            'total_net' => $totalNet,
            'total_vat' => $totalVat,
            'total_gross' => $totalGross,
        ];
    }

    /** Kaufmaennische Rundung (Half-Up, von null weg) auf 2 Stellen. */
    private function round2(string $value): string
    {
        $half = bccomp($value, '0', self::SCALE) < 0 ? '-0.005' : '0.005';

        return bcadd($value, $half, 2);
    }
}
