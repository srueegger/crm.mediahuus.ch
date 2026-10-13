<?php

declare(strict_types=1);

namespace App\Services;

final class PhoneNormalizer
{
    /**
     * Reduziert eine Telefonnummer auf Ziffern. Ein führendes "+" wird als "00"
     * gelesen, "0041" wird zu "0" (Schweizer Nationalvorwahl). Leere Eingaben
     * ergeben null.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);
        if (str_starts_with($trimmed, '+')) {
            $trimmed = '00' . substr($trimmed, 1);
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00410')) {
            $digits = '0' . substr($digits, 5);
        } elseif (str_starts_with($digits, '0041')) {
            $digits = '0' . substr($digits, 4);
        }

        return $digits;
    }
}
