<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Bereitet Texte fuer den QR-Zahlteil auf: Zeichen ausserhalb von Windows-1252
 * werden nach Latin/ASCII transliteriert, alle anderen bleiben unveraendert.
 */
class QrTextNormalizer
{
    private ?\Transliterator $transliterator = null;

    public function normalize(string $value): string
    {
        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return '';
        }

        $result = '';
        foreach ($chars as $char) {
            $result .= $this->isCp1252($char) ? $char : $this->transliterate($char);
        }

        return trim($result);
    }

    /**
     * Pruefung fuer die Eingabevalidierung: Ergebnis nicht leer und vollstaendig in Windows-1252 darstellbar.
     */
    public function isRepresentable(string $value): bool
    {
        $normalized = $this->normalize($value);

        return $normalized !== '' && $this->isCp1252($normalized);
    }

    private function isCp1252(string $text): bool
    {
        return @iconv('UTF-8', 'CP1252', $text) !== false;
    }

    private function transliterate(string $char): string
    {
        $this->transliterator ??= \Transliterator::create('Any-Latin; Latin-ASCII');
        $out = $this->transliterator?->transliterate($char);

        // Nicht transliterierbare Zeichen (z. B. Emoji) werden entfernt
        return is_string($out) && $this->isCp1252($out) ? preg_replace('/[^\x20-\x7E]/', '', $out) ?? '' : '';
    }
}
