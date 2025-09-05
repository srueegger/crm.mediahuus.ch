<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Estimate;
use Doctrine\DBAL\Connection;

class EstimateRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Estimate $estimate): int
    {
        $this->connection->insert('estimates', [
            'document_id' => $estimate->getDocumentId(),
            'damage_type' => $estimate->getDamageType(),
            'device_name' => $estimate->getDeviceName(),
            'serial_number' => $estimate->getSerialNumber(),
            'issue_text' => $estimate->getIssueText(),
            'price_chf' => $estimate->getPriceChf(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?Estimate
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM estimates WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateEstimate($row) : null;
    }

    public function findByDocumentId(int $documentId): ?Estimate
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM estimates WHERE document_id = ?',
            [$documentId]
        );

        return $row ? $this->hydrateEstimate($row) : null;
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM estimates ORDER BY id DESC';
        $rows = $this->connection->fetchAllAssociative($sql);
        
        return array_map([$this, 'hydrateEstimate'], $rows);
    }

    public function update(Estimate $estimate): bool
    {
        if (!$estimate->getId()) {
            throw new \InvalidArgumentException('Estimate ID is required for update');
        }

        $affectedRows = $this->connection->update('estimates', [
            'damage_type' => $estimate->getDamageType(),
            'device_name' => $estimate->getDeviceName(),
            'serial_number' => $estimate->getSerialNumber(),
            'issue_text' => $estimate->getIssueText(),
            'price_chf' => $estimate->getPriceChf(),
        ], [
            'id' => $estimate->getId()
        ]);

        return $affectedRows > 0;
    }

    public function delete(int $id): bool
    {
        $affectedRows = $this->connection->delete('estimates', ['id' => $id]);
        return $affectedRows > 0;
    }

    private function hydrateEstimate(array $row): Estimate
    {
        return new Estimate(
            documentId: (int) $row['document_id'],
            damageType: $row['damage_type'] ?? 'other',
            deviceName: $row['device_name'],
            serialNumber: $row['serial_number'],
            issueText: $row['issue_text'],
            priceChf: (float) $row['price_chf'],
            id: (int) $row['id']
        );
    }
}