<?php

declare(strict_types=1);

namespace App\Services;

class InvoiceInputValidator
{
    private const MAX_NAME = 70;
    private const MAX_STREET = 70;
    private const MAX_ZIP = 16;
    private const MAX_CITY = 35;
    private const MAX_TOTAL = '999999999.99';

    private const MAX_DESCRIPTION = 500;

    private InvoiceCalculator $calculator;
    private QrTextNormalizer $normalizer;

    /**
     * @param array<string, mixed> $invoiceConfig Inhalt von config/invoice.php
     */
    public function __construct(private array $invoiceConfig, ?InvoiceCalculator $calculator = null)
    {
        $this->calculator = $calculator ?? new InvoiceCalculator();
        $this->normalizer = new QrTextNormalizer();
    }

    /**
     * @param array<string, mixed> $post
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    public function validate(array $post): array
    {
        $errors = [];

        $company = $this->str($post['company_name'] ?? '');
        $contact = $this->str($post['contact_name'] ?? '');
        $street = $this->str($post['street'] ?? '');
        $zip = $this->str($post['zip'] ?? '');
        $city = $this->str($post['city'] ?? '');

        if ($company === '' && $contact === '') {
            $errors['contact_name'] = 'Firma oder Name ist erforderlich';
        }
        if (mb_strlen($company) > self::MAX_NAME) {
            $errors['company_name'] = 'Die Firma darf maximal 70 Zeichen lang sein';
        }
        if (mb_strlen($contact) > self::MAX_NAME && !isset($errors['contact_name'])) {
            $errors['contact_name'] = 'Der Name darf maximal 70 Zeichen lang sein';
        }
        $this->checkAddressField($errors, 'street', $street, self::MAX_STREET, 'Strasse');
        $this->checkAddressField($errors, 'zip', $zip, self::MAX_ZIP, 'PLZ');
        $this->checkAddressField($errors, 'city', $city, self::MAX_CITY, 'Ort');

        $latinMessage = 'Bitte nur lateinische Schrift verwenden (z. B. keine kyrillischen oder griechischen Zeichen).';
        foreach (['company_name' => $company, 'contact_name' => $contact, 'street' => $street, 'zip' => $zip, 'city' => $city] as $key => $value) {
            if ($value !== '' && !isset($errors[$key]) && !$this->normalizer->isRepresentable($value)) {
                $errors[$key] = $latinMessage;
            }
        }

        $invoiceDate = $this->parseDate($this->str($post['invoice_date'] ?? ''));
        if ($invoiceDate === null) {
            $errors['invoice_date'] = 'Bitte ein gültiges Rechnungsdatum angeben';
        }

        $termRaw = $this->str($post['payment_term_days'] ?? '');
        $term = (int) ($this->invoiceConfig['default_payment_term_days'] ?? 10);
        if ($termRaw !== '') {
            if (preg_match('/^\d{1,3}$/', $termRaw) === 1 && (int) $termRaw <= 365) {
                $term = (int) $termRaw;
            } else {
                $errors['payment_term_days'] = 'Die Zahlungsfrist muss eine ganze Zahl zwischen 0 und 365 sein';
            }
        }

        $items = $this->validateItems($post['items'] ?? [], $errors);

        return [
            'errors' => $errors,
            'data' => [
                'company_name' => $company,
                'contact_name' => $contact,
                'street' => $street,
                'zip' => $zip,
                'city' => $city,
                'invoice_date' => $invoiceDate,
                'payment_term_days' => $term,
                'items' => $items,
            ],
        ];
    }

    /**
     * @param array<string, string> $errors
     * @return list<array{description: string, quantity: string, unit_price_gross: string, vat_rate: string}>
     */
    private function validateItems(mixed $rawItems, array &$errors): array
    {
        $items = [];
        $itemErrors = false;
        $nonEmpty = 0;

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        foreach ($rawItems as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $description = $this->str($row['description'] ?? '');
            $quantityRaw = $this->str($row['quantity'] ?? '');
            $priceRaw = $this->str($row['unit_price'] ?? '');
            $vatRaw = $this->str($row['vat_rate'] ?? '');

            // Leere Zeilen (weder Beschreibung noch Preis) werden ignoriert
            if ($description === '' && $priceRaw === '') {
                continue;
            }
            $nonEmpty++;
            $i = $key;

            if ($description === '') {
                $errors["items.$i.description"] = 'Beschreibung ist erforderlich';
                $itemErrors = true;
            }

            if (mb_strlen($description) > self::MAX_DESCRIPTION) {
                $errors["items.$i.description"] = 'Beschreibung darf höchstens 500 Zeichen lang sein.';
                $itemErrors = true;
            }

            $quantity = $this->parseDecimal($quantityRaw, 8);
            if ($quantity === null || bccomp($quantity, '0', 2) <= 0) {
                $errors["items.$i.quantity"] = 'Die Menge muss grösser als 0 sein';
                $itemErrors = true;
            }

            $price = $this->parseDecimal($priceRaw, 10);
            if ($price === null) {
                $errors["items.$i.unit_price"] = 'Bitte einen Preis von 0 oder mehr angeben';
                $itemErrors = true;
            }

            $vat = $this->canonicalVatRate($vatRaw);
            if ($vat === null) {
                $errors["items.$i.vat_rate"] = 'Ungültiger MWST-Satz';
                $itemErrors = true;
            }

            if ($description !== '' && mb_strlen($description) <= self::MAX_DESCRIPTION && $quantity !== null && $price !== null && $vat !== null
                && bccomp($quantity, '0', 2) > 0) {
                $items[] = [
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price_gross' => $price,
                    'vat_rate' => $vat,
                ];
            }
        }

        if ($nonEmpty === 0) {
            $errors['items'] = 'Mindestens eine Position ist erforderlich';
            return [];
        }

        if (!$itemErrors) {
            $total = $this->calculator->calculate(array_map(
                static fn(array $i): array => [
                    'quantity' => $i['quantity'],
                    'unit_price_gross' => $i['unit_price_gross'],
                    'vat_rate' => $i['vat_rate'],
                ],
                $items
            ))['total_gross'];
            if (bccomp($total, '0.01', 2) < 0 || bccomp($total, self::MAX_TOTAL, 2) > 0) {
                $errors['items'] = "Das Total inkl. MWST muss zwischen 0.01 und 999'999'999.99 CHF liegen";
            }
        }

        return $items;
    }

    private function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param array<string, string> $errors
     */
    private function checkAddressField(array &$errors, string $key, string $value, int $max, string $label): void
    {
        if ($value === '') {
            $errors[$key] = "$label ist erforderlich";
        } elseif (mb_strlen($value) > $max) {
            $errors[$key] = "$label darf maximal $max Zeichen lang sein";
        }
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $date;
    }

    /**
     * Nicht-negative Dezimalzahl mit max. 2 Nachkommastellen (Komma oder Punkt), als "n.nn".
     */
    private function parseDecimal(string $value, int $maxIntDigits): ?string
    {
        $value = str_replace(',', '.', $value);
        if (preg_match('/^\d{1,' . $maxIntDigits . '}(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }
        return bcadd($value, '0', 2);
    }

    /**
     * Ordnet die Eingabe numerisch einem erlaubten Satz zu und liefert dessen kanonische Schreibweise.
     */
    private function canonicalVatRate(string $value): ?string
    {
        $value = str_replace(',', '.', $value);
        if (preg_match('/^\d{1,2}(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }
        foreach ($this->invoiceConfig['vat_rates'] ?? [] as $allowed) {
            if (bccomp($value, (string) $allowed, 2) === 0) {
                return (string) $allowed;
            }
        }
        return null;
    }
}
