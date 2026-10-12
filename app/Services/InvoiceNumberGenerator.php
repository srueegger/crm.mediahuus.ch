<?php

declare(strict_types=1);

namespace App\Services;

class InvoiceNumberGenerator
{
    /**
     * Liefert die naechste Rechnungsnummer (RE-JJJJ-MM-TT-NN) fuer den Rechnungstag.
     */
    public function next(\DateTimeInterface $invoiceDate, ?string $lastNumberOfDay): string
    {
        $sequence = 1;
        if ($lastNumberOfDay !== null && $lastNumberOfDay !== '') {
            $position = strrpos($lastNumberOfDay, '-');
            $sequence = (int) substr($lastNumberOfDay, $position === false ? 0 : $position + 1) + 1;
        }

        return sprintf('RE-%s-%02d', $invoiceDate->format('Y-m-d'), $sequence);
    }

    /**
     * Basis der QR-Referenz: Rechnungsnummer ohne Bindestriche (RE2026101201).
     */
    public function referenceBase(string $invoiceNumber): string
    {
        return str_replace('-', '', $invoiceNumber);
    }
}
