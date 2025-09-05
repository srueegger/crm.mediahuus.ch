<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Doctrine\DBAL\Connection;

class ReceiptRepository
{
    private Connection $connection;
    private ReceiptItemRepository $receiptItemRepository;

    public function __construct(Connection $connection, ReceiptItemRepository $receiptItemRepository)
    {
        $this->connection = $connection;
        $this->receiptItemRepository = $receiptItemRepository;
    }

    public function create(Receipt $receipt): int
    {
        $this->connection->insert('receipts', [
            'document_id' => $receipt->getDocumentId(),
            'total_amount_chf' => $receipt->getTotalAmountChf(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?Receipt
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM receipts WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateReceipt($row) : null;
    }

    public function findByDocumentId(int $documentId): ?Receipt
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM receipts WHERE document_id = ?',
            [$documentId]
        );

        return $row ? $this->hydrateReceipt($row) : null;
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM receipts ORDER BY id DESC';
        $rows = $this->connection->fetchAllAssociative($sql);
        
        return array_map([$this, 'hydrateReceipt'], $rows);
    }

    public function update(Receipt $receipt): bool
    {
        if (!$receipt->getId()) {
            throw new \InvalidArgumentException('Receipt ID is required for update');
        }

        $affectedRows = $this->connection->update('receipts', [
            'total_amount_chf' => $receipt->getTotalAmountChf(),
        ], [
            'id' => $receipt->getId()
        ]);

        return $affectedRows > 0;
    }

    public function delete(int $id): bool
    {
        $affectedRows = $this->connection->delete('receipts', ['id' => $id]);
        return $affectedRows > 0;
    }

    private function hydrateReceipt(array $row): Receipt
    {
        $receipt = new Receipt(
            documentId: (int) $row['document_id'],
            totalAmountChf: (float) $row['total_amount_chf'],
            items: [],
            id: (int) $row['id']
        );

        // Load receipt items
        $items = $this->receiptItemRepository->findByReceiptId($receipt->getId());
        $receipt->setItems($items);

        return $receipt;
    }
}