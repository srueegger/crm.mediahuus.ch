<?php
declare(strict_types=1);

namespace App\Models;

class Estimate
{
    private ?int $id;
    private int $documentId;
    private string $damageType;
    private string $deviceName;
    private string $serialNumber;
    private string $issueText;
    private float $priceChf;

    public function __construct(
        int $documentId,
        string $damageType,
        string $deviceName,
        string $serialNumber,
        string $issueText,
        float $priceChf,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->documentId = $documentId;
        $this->damageType = $damageType;
        $this->deviceName = $deviceName;
        $this->serialNumber = $serialNumber;
        $this->issueText = $issueText;
        $this->priceChf = $priceChf;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getDamageType(): string
    {
        return $this->damageType;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function getIssueText(): string
    {
        return $this->issueText;
    }

    public function getPriceChf(): float
    {
        return $this->priceChf;
    }

    public function getFormattedPrice(): string
    {
        return 'CHF ' . number_format($this->priceChf, 2, '.', "'");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'damage_type' => $this->damageType,
            'device_name' => $this->deviceName,
            'serial_number' => $this->serialNumber,
            'issue_text' => $this->issueText,
            'price_chf' => $this->priceChf,
            'formatted_price' => $this->getFormattedPrice(),
        ];
    }
}