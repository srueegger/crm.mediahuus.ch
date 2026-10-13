<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\PhoneNormalizer;

class Customer
{
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        private ?int $id,
        private string $name,
        private ?string $companyName,
        private ?string $phone,
        private ?string $email,
        private ?string $street,
        private ?string $zip,
        private ?string $city,
        private ?string $notes,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getPhoneNormalized(): ?string
    {
        return PhoneNormalizer::normalize($this->phone);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getDisplayName(): string
    {
        if ($this->companyName === null || $this->companyName === '' || $this->companyName === $this->name) {
            return $this->name;
        }

        return $this->companyName . ', ' . $this->name;
    }

    /**
     * Neue Instanz; nur nicht-null Felder (Name nur, wenn nicht leer) überschreiben.
     */
    public function withData(CustomerData $data): self
    {
        return new self(
            $this->id,
            $data->name !== '' ? $data->name : $this->name,
            $data->company ?? $this->companyName,
            $data->phone ?? $this->phone,
            $data->email ?? $this->email,
            $data->street ?? $this->street,
            $data->zip ?? $this->zip,
            $data->city ?? $this->city,
            $this->notes,
            $this->createdAt,
            $this->updatedAt
        );
    }

    public function hasSameData(Customer $other): bool
    {
        return $this->name === $other->name
            && $this->companyName === $other->companyName
            && $this->phone === $other->phone
            && $this->email === $other->email
            && $this->street === $other->street
            && $this->zip === $other->zip
            && $this->city === $other->city
            && $this->notes === $other->notes;
    }

    /**
     * Neue Instanz: leere Felder dieses Kunden werden aus der Quelle ergänzt.
     */
    public function mergedWith(Customer $source): self
    {
        $notes = $this->notes;
        if ($notes !== null && $notes !== '' && $source->notes !== null && $source->notes !== '') {
            $notes = $notes . "\n\n" . $source->notes;
        } elseif ($notes === null || $notes === '') {
            $notes = $source->notes;
        }

        return new self(
            $this->id,
            $this->name !== '' ? $this->name : $source->name,
            self::pick($this->companyName, $source->companyName),
            self::pick($this->phone, $source->phone),
            self::pick($this->email, $source->email),
            self::pick($this->street, $source->street),
            self::pick($this->zip, $source->zip),
            self::pick($this->city, $source->city),
            $notes,
            $this->createdAt,
            $this->updatedAt
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->companyName,
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'notes' => $this->notes,
            'display_name' => $this->getDisplayName(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private static function pick(?string $own, ?string $other): ?string
    {
        return $own !== null && $own !== '' ? $own : $other;
    }
}
