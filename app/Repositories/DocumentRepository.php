<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Document;
use Doctrine\DBAL\Connection;

class DocumentRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Document $document): int
    {
        $this->connection->insert('documents', [
            'doc_type' => $document->getDocType(),
            'doc_number' => $document->getDocNumber(),
            'branch_id' => $document->getBranchId(),
            'user_id' => $document->getUserId(),
            'customer_id' => $document->getCustomerId(),
            'customer_name' => $document->getCustomerName(),
            'customer_phone' => $document->getCustomerPhone(),
            'customer_email' => $document->getCustomerEmail(),
            'customer_street' => $document->getCustomerStreet(),
            'customer_zip' => $document->getCustomerZip(),
            'customer_city' => $document->getCustomerCity(),
            'created_at' => $document->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $document->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?Document
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM documents WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateDocument($row) : null;
    }

    public function findByDocNumber(string $docNumber): ?Document
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM documents WHERE doc_number = ?',
            [$docNumber]
        );

        return $row ? $this->hydrateDocument($row) : null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM documents';
        $params = [];
        $conditions = [];

        if (!empty($filters['doc_type'])) {
            $conditions[] = 'doc_type = ?';
            $params[] = $filters['doc_type'];
        }

        if (!empty($filters['branch_id'])) {
            $conditions[] = 'branch_id = ?';
            $params[] = $filters['branch_id'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY created_at DESC';

        $rows = $this->connection->fetchAllAssociative($sql, $params);
        
        return array_map([$this, 'hydrateDocument'], $rows);
    }

    /**
     * @return Document[] neueste zuerst
     */
    public function findByCustomerId(int $customerId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM documents WHERE customer_id = ? ORDER BY created_at DESC, id DESC',
            [$customerId]
        );

        return array_map([$this, 'hydrateDocument'], $rows);
    }

    public function setCustomerId(int $documentId, int $customerId): void
    {
        $this->connection->update('documents', ['customer_id' => $customerId], ['id' => $documentId]);
    }

    /**
     * Verschiebt alle Dokumente eines Kunden zu einem anderen; liefert die Anzahl.
     */
    public function reassignCustomer(int $fromCustomerId, int $toCustomerId): int
    {
        return (int) $this->connection->executeStatement(
            'UPDATE documents SET customer_id = ? WHERE customer_id = ?',
            [$toCustomerId, $fromCustomerId]
        );
    }

    /**
     * @return Document[] älteste zuerst
     */
    public function findWithoutCustomer(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM documents WHERE customer_id IS NULL ORDER BY created_at ASC, id ASC'
        );

        return array_map([$this, 'hydrateDocument'], $rows);
    }

    public function generateDocNumber(string $docType): string
    {
        if ($docType === Document::TYPE_INVOICE) {
            throw new \InvalidArgumentException(
                'Rechnungsnummern werden über InvoiceNumberGenerator vergeben.'
            );
        }

        $prefix = match($docType) {
            Document::TYPE_ESTIMATE => 'KO',
            Document::TYPE_PURCHASE => 'AK',
            Document::TYPE_INSURANCE => 'VG',
            Document::TYPE_RECEIPT => 'QU',
            Document::TYPE_REPAIR => 'RP',
            default => 'DOC'
        };

        $year = date('Y');

        // Höchste vorhandene Nummer dieses Präfixes und Jahres ermitteln
        $last = $this->connection->fetchOne(
            'SELECT doc_number FROM documents WHERE doc_number LIKE ? AND doc_number REGEXP ? '
            . 'ORDER BY doc_number DESC LIMIT 1',
            [$prefix . '-' . $year . '-%', '^' . $prefix . '-' . $year . '-[0-9]{6}$']
        );

        $nextNumber = 1;
        if (is_string($last) && $last !== '') {
            $nextNumber = (int) substr($last, (int) strrpos($last, '-') + 1) + 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $nextNumber);
    }

    public function findLastInvoiceNumberForDate(\DateTimeInterface $date): ?string
    {
        $last = $this->connection->fetchOne(
            'SELECT doc_number FROM documents WHERE doc_type = ? AND doc_number LIKE ? '
            . 'ORDER BY LENGTH(doc_number) DESC, doc_number DESC LIMIT 1',
            [Document::TYPE_INVOICE, 'RE-' . $date->format('Y-m-d') . '-%']
        );

        return is_string($last) && $last !== '' ? $last : null;
    }

    public function update(Document $document): bool
    {
        if (!$document->getId()) {
            throw new \InvalidArgumentException('Document ID is required for update');
        }

        $affectedRows = $this->connection->update('documents', [
            'doc_type' => $document->getDocType(),
            'doc_number' => $document->getDocNumber(),
            'branch_id' => $document->getBranchId(),
            'customer_name' => $document->getCustomerName(),
            'customer_phone' => $document->getCustomerPhone(),
            'customer_email' => $document->getCustomerEmail(),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], [
            'id' => $document->getId()
        ]);

        return $affectedRows > 0;
    }

    public function delete(int $id): bool
    {
        $affectedRows = $this->connection->delete('documents', ['id' => $id]);
        return $affectedRows > 0;
    }

    /**
     * Baut ein Document aus einer Datenbankzeile (für Repositories mit JOIN auf documents).
     */
    public function hydrateRow(array $row): Document
    {
        return $this->hydrateDocument($row);
    }

    private function hydrateDocument(array $row): Document
    {
        return new Document(
            docType: $row['doc_type'],
            docNumber: $row['doc_number'],
            branchId: (int) $row['branch_id'],
            userId: (int) $row['user_id'],
            customerName: $row['customer_name'],
            customerPhone: $row['customer_phone'],
            customerEmail: $row['customer_email'],
            id: (int) $row['id'],
            createdAt: new \DateTime($row['created_at']),
            updatedAt: new \DateTime($row['updated_at']),
            customerStreet: $row['customer_street'] ?? null,
            customerZip: $row['customer_zip'] ?? null,
            customerCity: $row['customer_city'] ?? null,
            customerId: isset($row['customer_id']) ? (int) $row['customer_id'] : null
        );
    }
}