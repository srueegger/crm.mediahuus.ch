<?php
declare(strict_types=1);

namespace App\Models;

class Branch
{
    private ?int $id;
    private string $name;
    private string $street;
    private string $zip;
    private string $city;
    private ?string $phone;
    private ?string $email;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $name,
        string $street,
        string $zip,
        string $city,
        ?string $phone = null,
        ?string $email = null,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
        $this->phone = $phone;
        $this->email = $email;
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

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getFullAddress(): string
    {
        return "{$this->street}, {$this->zip} {$this->city}";
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'full_address' => $this->getFullAddress(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}