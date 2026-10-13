<?php

declare(strict_types=1);

namespace App\Services;

final class RepairInputValidator
{
    private const MAX_LENGTHS = [
        'device_name' => 255,
        'imei_serial' => 64,
        'accessories' => 255,
        'unlock_code' => 100,
    ];

    /**
     * @param array<string, mixed> $form
     * @return array<string, string> Feld => Fehlermeldung
     */
    public function validate(array $form): array
    {
        $errors = [];
        $get = static fn (string $key): string => is_string($form[$key] ?? null) ? trim($form[$key]) : '';

        if ($get('branch_id') === '' || !ctype_digit($get('branch_id')) || (int) $get('branch_id') < 1) {
            $errors['branch_id'] = 'Bitte eine Filiale wählen.';
        }
        if ($get('customer_name') === '') {
            $errors['customer_name'] = 'Bitte den Kundennamen angeben.';
        }
        if ($get('device_name') === '') {
            $errors['device_name'] = 'Bitte das Gerät angeben.';
        }
        if ($get('issue_text') === '') {
            $errors['issue_text'] = 'Bitte die Fehlerbeschreibung angeben.';
        }

        foreach (self::MAX_LENGTHS as $field => $max) {
            if (!isset($errors[$field]) && mb_strlen($get($field)) > $max) {
                $errors[$field] = "Maximal {$max} Zeichen erlaubt.";
            }
        }

        $price = $get('price_chf');
        if ($price !== '') {
            $normalized = str_replace(',', '.', $price);
            if (!preg_match('/^\d+(\.\d+)?$/', $normalized)) {
                $errors['price_chf'] = 'Bitte einen gültigen Preis (0 oder höher) angeben.';
            }
        }

        $due = $get('due_date');
        if ($due !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $due);
            if ($date === false || $date->format('Y-m-d') !== $due) {
                $errors['due_date'] = 'Bitte ein gültiges Datum angeben.';
            }
        }

        $email = $get('customer_email');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['customer_email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
        }

        $damage = $get('damage_type');
        if ($damage !== '' && !array_key_exists($damage, DamageTypeService::DAMAGE_TYPES)) {
            $errors['damage_type'] = 'Unbekannter Schadenstyp.';
        }

        return $errors;
    }
}
