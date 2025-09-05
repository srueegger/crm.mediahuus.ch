<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\ReceiptItem;
use Doctrine\DBAL\Connection;

class ReceiptItemRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(ReceiptItem $item): int
    {
        $this->connection->insert('receipt_items', [
            'receipt_id' => $item->getReceiptId(),
            'item_description' => $item->getItemDescription(),
            'quantity' => $item->getQuantity(),
            'unit_price_chf' => $item->getUnitPriceChf(),
            'line_total_chf' => $item->getLineTotalChf(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?ReceiptItem
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM receipt_items WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateReceiptItem($row) : null;
    }

    public function findByReceiptId(int $receiptId): array
    {
        $sql = 'SELECT * FROM receipt_items WHERE receipt_id = ? ORDER BY id ASC';
        $rows = $this->connection->fetchAllAssociative($sql, [$receiptId]);
        
        return array_map([$this, 'hydrateReceiptItem'], $rows);
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM receipt_items ORDER BY id DESC';
        $rows = $this->connection->fetchAllAssociative($sql);
        
        return array_map([$this, 'hydrateReceiptItem'], $rows);
    }

    public function update(ReceiptItem $item): bool
    {
        if (!$item->getId()) {
            throw new \InvalidArgumentException('ReceiptItem ID is required for update');
        }

        $affectedRows = $this->connection->update('receipt_items', [
            'item_description' => $item->getItemDescription(),
            'quantity' => $item->getQuantity(),
            'unit_price_chf' => $item->getUnitPriceChf(),
            'line_total_chf' => $item->getLineTotalChf(),
        ], [
            'id' => $item->getId()
        ]);

        return $affectedRows > 0;
    }

    public function delete(int $id): bool
    {
        $affectedRows = $this->connection->delete('receipt_items', ['id' => $id]);
        return $affectedRows > 0;
    }

    public function deleteByReceiptId(int $receiptId): bool
    {
        $affectedRows = $this->connection->delete('receipt_items', ['receipt_id' => $receiptId]);
        return $affectedRows > 0;
    }

    private function hydrateReceiptItem(array $row): ReceiptItem
    {
        return new ReceiptItem(
            receiptId: (int) $row['receipt_id'],
            itemDescription: $row['item_description'],
            quantity: (int) $row['quantity'],
            unitPriceChf: (float) $row['unit_price_chf'],
            lineTotalChf: (float) $row['line_total_chf'],
            id: (int) $row['id']
        );
    }
}