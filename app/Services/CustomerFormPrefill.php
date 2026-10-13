<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Repositories\CustomerRepository;

/**
 * Belegt Dokumentformulare aus einem bestehenden Kunden vor.
 */
class CustomerFormPrefill
{
    public function __construct(private CustomerRepository $customers)
    {
    }

    /**
     * @return array{formData: array<string, string>, linkedCustomer: ?array<string, mixed>}
     */
    public function forRequest(string $docType, mixed $customerIdParam): array
    {
        $customer = $this->find($customerIdParam);
        if ($customer === null) {
            return ['formData' => [], 'linkedCustomer' => null];
        }

        $values = [
            'name' => $customer->getName(),
            'company' => $customer->getCompanyName(),
            'phone' => $customer->getPhone(),
            'email' => $customer->getEmail(),
            'street' => $customer->getStreet(),
            'zip' => $customer->getZip(),
            'city' => $customer->getCity(),
        ];

        // Rechnung: Ist der Name gleich der Firma, bleibt der Ansprechpartner leer.
        if ($docType === Document::TYPE_INVOICE && $values['name'] === $values['company']) {
            $values['name'] = null;
        }

        $formData = [];
        foreach (CustomerFieldMap::for($docType) as $attribute => $field) {
            $value = $values[$attribute] ?? null;
            if ($value !== null && $value !== '') {
                $formData[$field] = $value;
            }
        }
        $formData['customer_id'] = (string) $customer->getId();

        return ['formData' => $formData, 'linkedCustomer' => $customer->toArray()];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function linked(mixed $customerId): ?array
    {
        return $this->find($customerId)?->toArray();
    }

    private function find(mixed $param): ?Customer
    {
        if (is_int($param)) {
            $id = $param;
        } elseif (is_string($param) && preg_match('/^\d+$/', $param) === 1) {
            $id = (int) $param;
        } else {
            return null;
        }

        return $id > 0 ? $this->customers->findById($id) : null;
    }
}
