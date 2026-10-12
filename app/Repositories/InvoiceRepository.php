<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Doctrine\DBAL\Connection;

class InvoiceRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * Speichert Rechnung und Positionen. Die Transaktion steuert der Aufrufer.
     */
    public function create(Invoice $invoice): int
    {
        $this->connection->insert('invoices', [
            'document_id' => $invoice->getDocumentId(),
            'company_name' => $invoice->getCompanyName(),
            'contact_name' => $invoice->getContactName(),
            'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
            'payment_term_days' => $invoice->getPaymentTermDays(),
            'due_date' => $invoice->getDueDate()->format('Y-m-d'),
            'total_net_chf' => $invoice->getTotalNetChf(),
            'total_vat_chf' => $invoice->getTotalVatChf(),
            'total_gross_chf' => $invoice->getTotalGrossChf(),
            'qr_reference' => $invoice->getQrReference(),
        ]);
        $invoiceId = (int) $this->connection->lastInsertId();

        foreach ($invoice->getItems() as $item) {
            $this->connection->insert('invoice_items', [
                'invoice_id' => $invoiceId,
                'position' => $item->getPosition(),
                'description' => $item->getDescription(),
                'quantity' => $item->getQuantity(),
                'unit_price_gross_chf' => $item->getUnitPriceGrossChf(),
                'vat_rate' => $item->getVatRate(),
                'line_total_gross_chf' => $item->getLineTotalGrossChf(),
            ]);
        }

        return $invoiceId;
    }

    public function findByDocumentId(int $documentId): ?Invoice
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM invoices WHERE document_id = ?',
            [$documentId]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /** @return Invoice[] */
    public function findAll(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM invoices ORDER BY id DESC');

        return array_map([$this, 'hydrate'], $rows);
    }

    private function hydrate(array $row): Invoice
    {
        $itemRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY position ASC',
            [(int) $row['id']]
        );

        $items = array_map(static fn(array $r): InvoiceItem => new InvoiceItem(
            position: (int) $r['position'],
            description: (string) $r['description'],
            quantity: (string) $r['quantity'],
            unitPriceGrossChf: (string) $r['unit_price_gross_chf'],
            vatRate: self::canonicalRate((string) $r['vat_rate']),
            lineTotalGrossChf: (string) $r['line_total_gross_chf'],
            id: (int) $r['id'],
            invoiceId: (int) $r['invoice_id']
        ), $itemRows);

        return new Invoice(
            documentId: (int) $row['document_id'],
            companyName: $row['company_name'] !== null ? (string) $row['company_name'] : null,
            contactName: $row['contact_name'] !== null ? (string) $row['contact_name'] : null,
            invoiceDate: new \DateTimeImmutable((string) $row['invoice_date']),
            paymentTermDays: (int) $row['payment_term_days'],
            dueDate: new \DateTimeImmutable((string) $row['due_date']),
            totalNetChf: (string) $row['total_net_chf'],
            totalVatChf: (string) $row['total_vat_chf'],
            totalGrossChf: (string) $row['total_gross_chf'],
            qrReference: (string) $row['qr_reference'],
            items: $items,
            id: (int) $row['id']
        );
    }

    /** DECIMAL(4,2) "8.10" -> "8.1", "0.00" -> "0" (Schreibweise der Konfiguration) */
    private static function canonicalRate(string $rate): string
    {
        if (str_contains($rate, '.')) {
            $rate = rtrim(rtrim($rate, '0'), '.');
        }
        return $rate === '' ? '0' : $rate;
    }
}
