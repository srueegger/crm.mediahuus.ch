<?php
declare(strict_types=1);

namespace App\Models;

class ReceiptItem
{
    private ?int $id;
    private int $receiptId;
    private string $itemDescription;
    private int $quantity;
    private float $unitPriceChf;
    private float $lineTotalChf;

    public function __construct(
        int $receiptId,
        string $itemDescription,
        int $quantity,
        float $unitPriceChf,
        float $lineTotalChf,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->receiptId = $receiptId;
        $this->itemDescription = $itemDescription;
        $this->quantity = $quantity;
        $this->unitPriceChf = $unitPriceChf;
        $this->lineTotalChf = $lineTotalChf;
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
        ];
    }
}