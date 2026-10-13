<?php

declare(strict_types=1);

namespace App\Models;

final class CustomerData
{
    public readonly string $name;
    public readonly ?string $company;
    public readonly ?string $phone;
    public readonly ?string $email;
    public readonly ?string $street;
    public readonly ?string $zip;
    public readonly ?string $city;

    public function __construct(
        ?string $name,
        ?string $company = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $street = null,
        ?string $zip = null,
        ?string $city = null,
    ) {
        $this->name = trim((string) $name);
        $this->company = self::nullIfBlank($company);
        $this->phone = self::nullIfBlank($phone);
        $this->email = self::nullIfBlank($email);
        $this->street = self::nullIfBlank($street);
        $this->zip = self::nullIfBlank($zip);
        $this->city = self::nullIfBlank($city);
    }

    public static function normalizeName(string $name): string
    {
        return mb_strtolower(self::collapseWhitespace($name));
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $value = self::nullIfBlank($email);

        return $value === null ? null : mb_strtolower($value);
    }

    public static function normalizeStreet(?string $street): ?string
    {
        $value = self::nullIfBlank($street);

        return $value === null ? null : mb_strtolower(self::collapseWhitespace($value));
    }

    public function isEmpty(): bool
    {
        return $this->name === '';
    }

    private static function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
