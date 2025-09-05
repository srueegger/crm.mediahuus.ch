<?php
declare(strict_types=1);

namespace App\Models;

class Document
{
    public const TYPE_ESTIMATE = 'estimate';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_RECEIPT = 'receipt';

    private ?int $id;
    private string $docType;
    private string $docNumber;
    private int $branchId;
    private int $userId;
    private string $customerName;
    private ?string $customerPhone;
    private ?string $customerEmail;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $docType,
        string $docNumber,
        int $branchId,
        int $userId,
        string $customerName,
        ?string $customerPhone = null,
        ?string $customerEmail = null,
        ?int $id = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->validateDocType($docType);
        
        $this->id = $id;
        $this->docType = $docType;
        $this->docNumber = $docNumber;
        $this->branchId = $branchId;
        $this->userId = $userId;
        $this->customerName = $customerName;
        $this->customerPhone = $customerPhone;
        $this->customerEmail = $customerEmail;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocType(): string
    {
        return $this->docType;
    }

    public function getDocNumber(): string
    {
        return $this->docNumber;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'doc_type' => $this->docType,
            'doc_number' => $this->docNumber,
            'branch_id' => $this->branchId,
            'user_id' => $this->userId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_email' => $this->customerEmail,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function getValidTypes(): array
    {
        return [
            self::TYPE_ESTIMATE,
            self::TYPE_PURCHASE,
            self::TYPE_INSURANCE,
            self::TYPE_RECEIPT
        ];
    }

    public function getTypeLabel(): string
    {
        return match($this->docType) {
            self::TYPE_ESTIMATE => 'Kostenvoranschlag',
            self::TYPE_PURCHASE => 'Ankauf',
            self::TYPE_INSURANCE => 'Versicherungsgutachten',
            self::TYPE_RECEIPT => 'Quittung',
            default => 'Unbekannt'
        };
    }

    private function validateDocType(string $docType): void
    {
        if (!in_array($docType, self::getValidTypes())) {
            throw new \InvalidArgumentException("Invalid document type: {$docType}");
        }
    }
}