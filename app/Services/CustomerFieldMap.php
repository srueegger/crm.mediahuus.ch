<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerData;
use App\Models\Document;

/**
 * Einzige Stelle, die weiss, welches Formularfeld welches Kundenattribut enthält.
 */
final class CustomerFieldMap
{
    /**
     * @return array<string, string> Kundenattribut => Formularfeld
     */
    public static function for(string $docType): array
    {
        return match ($docType) {
            Document::TYPE_ESTIMATE => [
                'name' => 'customer_name',
                'phone' => 'customer_phone',
                'email' => 'customer_email',
            ],
            Document::TYPE_PURCHASE => [
                'name' => 'customer_name',
                'phone' => 'customer_phone',
                'email' => 'customer_email',
                'street' => 'seller_street',
                'zip' => 'seller_zip',
                'city' => 'seller_city',
            ],
            Document::TYPE_RECEIPT => [
                'name' => 'customer_name',
                'street' => 'customer_street',
                'zip' => 'customer_zip',
                'city' => 'customer_city',
            ],
            Document::TYPE_INSURANCE => [
                'name' => 'customer_name',
                'phone' => 'customer_phone',
                'email' => 'customer_email',
                'street' => 'customer_street',
                'zip' => 'customer_zip',
                'city' => 'customer_city',
            ],
            Document::TYPE_INVOICE => [
                'name' => 'contact_name',
                'company' => 'company_name',
                'street' => 'street',
                'zip' => 'zip',
                'city' => 'city',
            ],
            default => throw new \InvalidArgumentException('Unknown document type: ' . $docType),
        };
    }

    /**
     * @param array<string, mixed> $form
     */
    public static function dataFromForm(string $docType, array $form): CustomerData
    {
        $map = self::for($docType);
        $get = static function (string $attribute) use ($map, $form): ?string {
            $field = $map[$attribute] ?? null;
            $value = $field !== null ? ($form[$field] ?? null) : null;

            return is_string($value) ? $value : null;
        };

        $name = $get('name');
        $company = $get('company');

        if ($docType === Document::TYPE_INVOICE && trim((string) $name) === '') {
            $name = $company;
        }

        return new CustomerData(
            $name,
            $company,
            $get('phone'),
            $get('email'),
            $get('street'),
            $get('zip'),
            $get('city'),
        );
    }
}
