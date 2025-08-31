<?php
declare(strict_types=1);

namespace App\Models;

class Estimate
{
    private ?int $id;
    private int $documentId;
    private string $issueText;
    private float $priceChf;

    public function __construct(
        int $documentId,
        string $issueText,
        float $priceChf,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->documentId = $documentId;
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
            'issue_text' => $this->issueText,
            'price_chf' => $this->priceChf,
            'formatted_price' => $this->getFormattedPrice(),
        ];
    }
}