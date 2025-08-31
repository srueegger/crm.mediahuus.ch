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
            'customer_name' => $document->getCustomerName(),
            'customer_phone' => $document->getCustomerPhone(),
            'customer_email' => $document->getCustomerEmail(),
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

    public function generateDocNumber(string $docType): string
    {
        $prefix = match($docType) {
            Document::TYPE_ESTIMATE => 'KO',
            Document::TYPE_PURCHASE => 'AK',
            Document::TYPE_INSURANCE => 'VG',
            default => 'DOC'
        };

        $year = date('Y');
        
        // Get the next sequential number for this year and type
        $sql = 'SELECT COUNT(*) FROM documents WHERE doc_type = ? AND YEAR(created_at) = ?';
        $count = $this->connection->fetchOne($sql, [$docType, $year]);
        
        $nextNumber = $count + 1;
        
        return sprintf('%s-%s-%06d', $prefix, $year, $nextNumber);
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
            updatedAt: new \DateTime($row['updated_at'])
        );
    }
}