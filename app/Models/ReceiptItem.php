<?php
declare(strict_types=1);

namespace App\Models;

class ReceiptItem
{
    public const WARRANTY_1_MONTH = '1_month';
    public const WARRANTY_3_MONTHS = '3_months';
    public const WARRANTY_6_MONTHS = '6_months';
    public const WARRANTY_12_MONTHS = '12_months';
    public const WARRANTY_24_MONTHS = '24_months';

    private ?int $id;
    private int $receiptId;
    private string $itemDescription;
    private int $quantity;
    private float $unitPriceChf;
    private float $lineTotalChf;
    private ?string $warranty;

    public function __construct(
        int $receiptId,
        string $itemDescription,
        int $quantity,
        float $unitPriceChf,
        float $lineTotalChf,
        ?int $id = null,
        ?string $warranty = null
    ) {
        $this->id = $id;
        $this->receiptId = $receiptId;
        $this->itemDescription = $itemDescription;
        $this->quantity = $quantity;
        $this->unitPriceChf = $unitPriceChf;
        $this->lineTotalChf = $lineTotalChf;
        $this->warranty = $warranty;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptId(): int
    {
        return $this->receiptId;
    }

    public function getItemDescription(): string
    {
        return $this->itemDescription;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPriceChf(): float
    {
        return $this->unitPriceChf;
    }

    public function getLineTotalChf(): float
    {
        return $this->lineTotalChf;
    }

    public function getWarranty(): ?string
    {
        return $this->warranty;
    }

    public function getWarrantyLabel(): ?string
    {
        if (!$this->warranty) {
            return null;
        }
        return match($this->warranty) {
            self::WARRANTY_1_MONTH => '1 Monat',
            self::WARRANTY_3_MONTHS => '3 Monate',
            self::WARRANTY_6_MONTHS => '6 Monate',
            self::WARRANTY_12_MONTHS => '12 Monate',
            self::WARRANTY_24_MONTHS => '24 Monate',
            default => null
        };
    }

    public static function getWarrantyOptions(): array
    {
        return [
            self::WARRANTY_1_MONTH => '1 Monat',
            self::WARRANTY_3_MONTHS => '3 Monate',
            self::WARRANTY_6_MONTHS => '6 Monate',
            self::WARRANTY_12_MONTHS => '12 Monate',
            self::WARRANTY_24_MONTHS => '24 Monate',
        ];
    }

    public function getFormattedUnitPrice(): string
    {
        return 'CHF ' . number_format($this->unitPriceChf, 2, '.', "'");
    }

    public function getFormattedLineTotal(): string
    {
        return 'CHF ' . number_format($this->lineTotalChf, 2, '.', "'");
    }

    /**
     * Calculate line total from quantity and unit price
     */
    public static function calculateLineTotal(int $quantity, float $unitPrice): float
    {
        return $quantity * $unitPrice;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'receipt_id' => $this->receiptId,
            'item_description' => $this->itemDescription,
            'quantity' => $this->quantity,
            'unit_price_chf' => $this->unitPriceChf,
            'line_total_chf' => $this->lineTotalChf,
            'formatted_unit_price' => $this->getFormattedUnitPrice(),
            'formatted_line_total' => $this->getFormattedLineTotal(),
            'warranty' => $this->warranty,
            'warranty_label' => $this->getWarrantyLabel(),
        ];
    }
}