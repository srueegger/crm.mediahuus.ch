<?php
declare(strict_types=1);

namespace App\Models;

class Receipt
{
    private ?int $id;
    private int $documentId;
    private float $totalAmountChf;
    private array $items;

    public function __construct(
        int $documentId,
        float $totalAmountChf,
        array $items = [],
        ?int $id = null
    ) {
        $this->id = $id;
        $this->documentId = $documentId;
        $this->totalAmountChf = $totalAmountChf;
        $this->items = $items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getTotalAmountChf(): float
    {
        return $this->totalAmountChf;
    }

    public function getFormattedTotal(): string
    {
        return 'CHF ' . number_format($this->totalAmountChf, 2, '.', "'");
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(ReceiptItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getLineTotalChf();
        }
        return $total;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'total_amount_chf' => $this->totalAmountChf,
            'formatted_total' => $this->getFormattedTotal(),
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }
}