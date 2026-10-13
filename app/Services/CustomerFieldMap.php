<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerData;
use App\Models\Document;
use App\Models\InsuranceAssessment;
use App\Models\Invoice;
use App\Models\Purchase;

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

    /**
     * Der Name «Barkauf» (beliebige Schreibweise) kennzeichnet eine anonyme Quittung.
     */
    public static function isAnonymousReceiptName(string $name): bool
    {
        return mb_strtolower(trim($name)) === 'barkauf';
    }

    /**
     * Baut die Kundendaten eines bestehenden Dokuments (Altdaten-Import).
     * Eine Quittung mit dem Namen «Barkauf» gilt als anonym (leerer Name).
     */
    public static function dataFromDocument(
        Document $doc,
        ?Purchase $purchase,
        ?InsuranceAssessment $assessment,
        ?Invoice $invoice
    ): CustomerData {
        $name = $doc->getCustomerName();
        $company = null;
        $phone = $doc->getCustomerPhone();
        $email = $doc->getCustomerEmail();
        $street = $doc->getCustomerStreet();
        $zip = $doc->getCustomerZip();
        $city = $doc->getCustomerCity();

        switch ($doc->getDocType()) {
            case Document::TYPE_ESTIMATE:
                $street = $zip = $city = null;
                break;
            case Document::TYPE_PURCHASE:
                $street = $purchase?->getSellerStreet();
                $zip = $purchase?->getSellerZip();
                $city = $purchase?->getSellerCity();
                break;
            case Document::TYPE_INSURANCE:
                $street = $assessment?->getCustomerStreet();
                $zip = $assessment?->getCustomerZip();
                $city = $assessment?->getCustomerCity();
                break;
            case Document::TYPE_RECEIPT:
                $phone = $email = null;
                if (self::isAnonymousReceiptName($name)) {
                    $name = '';
                }
                break;
            case Document::TYPE_INVOICE:
                $phone = $email = null;
                $company = $invoice?->getCompanyName();
                $contact = trim((string) $invoice?->getContactName());
                $name = $contact !== '' ? $contact : (string) $company;
                break;
        }

        return new CustomerData($name, $company, $phone, $email, $street, $zip, $city);
    }
}
