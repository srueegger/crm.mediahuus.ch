<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\InvoiceCalculator;

class Invoice
{
    /**
     * @param InvoiceItem[] $items
     */
    public function __construct(
        private int $documentId,
        private ?string $companyName,
        private ?string $contactName,
        private \DateTimeImmutable $invoiceDate,
        private int $paymentTermDays,
        private \DateTimeImmutable $dueDate,
        private string $totalNetChf,
        private string $totalVatChf,
        private string $totalGrossChf,
        private string $qrReference,
        private array $items = [],
        private ?int $id = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /** Firma, sonst Name */
    public function getDisplayName(): string
    {
        return $this->companyName !== null && $this->companyName !== ''
            ? $this->companyName
            : (string) $this->contactName;
    }

    public function getInvoiceDate(): \DateTimeImmutable
    {
        return $this->invoiceDate;
    }

    public function getPaymentTermDays(): int
    {
        return $this->paymentTermDays;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getTotalNetChf(): string
    {
        return $this->totalNetChf;
    }

    public function getTotalVatChf(): string
    {
        return $this->totalVatChf;
    }

    public function getTotalGrossChf(): string
    {
        return $this->totalGrossChf;
    }

    public function getQrReference(): string
    {
        return $this->qrReference;
    }

    /** @return InvoiceItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @param InvoiceItem[] $items */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * MWST-Gruppen (Satz => gross/vat/net), neu berechnet aus den gespeicherten Positionen.
     *
     * @return array<string, array{gross: string, vat: string, net: string}>
     */
    public function getVatGroups(): array
    {
        $calc = (new InvoiceCalculator())->calculate(array_map(
            static fn(InvoiceItem $i): array => [
                'quantity' => $i->getQuantity(),
                'unit_price_gross' => $i->getUnitPriceGrossChf(),
                'vat_rate' => $i->getVatRate(),
            ],
            $this->items
        ));

        $groups = [];
        foreach ($calc['vat_groups'] as $rate => $group) {
            $groups[(string) $rate] = $group;
        }
        return $groups;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'invoice_date' => $this->invoiceDate->format('Y-m-d'),
            'payment_term_days' => $this->paymentTermDays,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'total_net_chf' => $this->totalNetChf,
            'total_vat_chf' => $this->totalVatChf,
            'total_gross_chf' => $this->totalGrossChf,
            'qr_reference' => $this->qrReference,
            'items' => array_map(static fn(InvoiceItem $i): array => $i->toArray(), $this->items),
            'vat_groups' => $this->getVatGroups(),
        ];
    }
}
