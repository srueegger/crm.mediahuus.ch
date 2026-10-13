<?php

declare(strict_types=1);

namespace App\Services;

final class TwigCacheConfig
{
    /**
     * Wertet TWIG_CACHE aus. "false", "0", "off", "no" oder leer → kein Cache (false).
     * "true", "1", "on", "yes" → Standardverzeichnis. Jeder andere Wert ist ein Pfad;
     * relative Pfade gelten ab Projektverzeichnis, nicht ab dem Arbeitsverzeichnis
     * (sonst landet der Cache in public/).
     */
    public static function resolve(?string $value, string $projectRoot): string|false
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return false;
        }

        $flag = filter_var($trimmed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($flag === false) {
            return false;
        }
        if ($flag === true) {
            return self::defaultDir($projectRoot);
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return rtrim($projectRoot, '/') . '/' . $trimmed;
    }

    public static function defaultDir(string $projectRoot): string
    {
        return rtrim($projectRoot, '/') . '/var/cache/twig';
    }
}
