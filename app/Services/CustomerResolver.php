<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerData;
use App\Repositories\CustomerRepository;

class CustomerResolver
{
    public function __construct(private CustomerRepository $customers)
    {
    }

    /**
     * Ermittelt den Kunden zu den Dokumentdaten (legt ihn bei Bedarf an).
     * Gibt null zurück, wenn kein Name vorhanden ist.
     */
    public function resolve(CustomerData $data, ?int $selectedCustomerId): ?int
    {
        if ($data->isEmpty()) {
            return null;
        }

        $wantedName = CustomerData::normalizeName($data->name);

        if ($selectedCustomerId !== null) {
            $selected = $this->customers->findById($selectedCustomerId);
            if ($selected !== null && CustomerData::normalizeName($selected->getName()) === $wantedName) {
                return $this->link($selected, $data);
            }
        }

        $phone = PhoneNormalizer::normalize($data->phone);
        $email = CustomerData::normalizeEmail($data->email);
        $street = CustomerData::normalizeStreet($data->street);
        $zip = $data->zip !== null ? trim($data->zip) : null;
        $addressKnown = $street !== null && $zip !== null;

        $candidates = $this->customers->findCandidates($phone, $email, $addressKnown ? $zip : null);

        $matches = [];
        foreach ($candidates as $candidate) {
            if (CustomerData::normalizeName($candidate->getName()) !== $wantedName) {
                continue;
            }

            $candidatePhone = $candidate->getPhoneNormalized();
            $candidateEmail = CustomerData::normalizeEmail($candidate->getEmail());
            $candidateStreet = CustomerData::normalizeStreet($candidate->getStreet());
            $candidateZip = $candidate->getZip() !== null ? trim($candidate->getZip()) : null;

            $samePhone = $phone !== null && $candidatePhone === $phone;
            $sameEmail = $email !== null && $candidateEmail === $email;
            $sameAddress = $addressKnown && $candidateStreet === $street && $candidateZip === $zip;

            if ($samePhone || $sameEmail || $sameAddress) {
                $matches[] = $candidate;
            }
        }

        if (count($matches) === 1) {
            return $this->link($matches[0], $data);
        }

        return $this->customers->create(new Customer(
            null,
            $data->name,
            $data->company,
            $data->phone,
            $data->email,
            $data->street,
            $data->zip,
            $data->city,
            null
        ));
    }

    private function link(Customer $customer, CustomerData $data): int
    {
        $updated = $customer->withData($data);
        if (!$updated->hasSameData($customer)) {
            $this->customers->update($updated);
        }

        return (int) $customer->getId();
    }
}
