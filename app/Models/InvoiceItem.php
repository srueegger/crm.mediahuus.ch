<?php

declare(strict_types=1);

namespace App\Models;

class InvoiceItem
{
    /**
     * Beträge und Mengen sind Dezimal-Strings (z. B. "49.90"); vatRate in kanonischer Form ("8.1", "2.6", "0").
     */
    public function __construct(
        private int $position,
        private string $description,
        private string $quantity,
        private string $unitPriceGrossChf,
        private string $vatRate,
        private string $lineTotalGrossChf,
        private ?int $id = null,
        private ?int $invoiceId = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitPriceGrossChf(): string
    {
        return $this->unitPriceGrossChf;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function getLineTotalGrossChf(): string
    {
        return $this->lineTotalGrossChf;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoiceId,
            'position' => $this->position,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_gross_chf' => $this->unitPriceGrossChf,
            'vat_rate' => $this->vatRate,
            'line_total_gross_chf' => $this->lineTotalGrossChf,
        ];
    }
}
