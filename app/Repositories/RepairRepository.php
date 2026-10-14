<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Document;
use App\Models\Repair;
use Doctrine\DBAL\Connection;

class RepairRepository
{
    private DocumentRepository $documents;

    public function __construct(private Connection $connection)
    {
        $this->documents = new DocumentRepository($connection);
    }

    public function create(Repair $repair): int
    {
        $this->connection->insert('repairs', $this->toRow($repair) + [
            'document_id' => $repair->getDocumentId(),
            'status_token' => $repair->getStatusToken(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(Repair $repair): void
    {
        if ($repair->getId() === null) {
            throw new \InvalidArgumentException('Repair ID is required for update');
        }

        $this->connection->update('repairs', $this->toRow($repair), ['id' => $repair->getId()]);
    }

    public function findByDocumentId(int $documentId): ?Repair
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM repairs WHERE document_id = ?', [$documentId]);

        return $row ? $this->hydrate($row) : null;
    }

    public function findByToken(string $token): ?Repair
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM repairs WHERE status_token = ?', [$token]);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @param array{branch_id?: mixed, status?: mixed, scope?: mixed} $filters
     * @return array{items: list<array{document: Document, repair: Repair}>, total: int}
     */
    public function findList(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['branch_id'])) {
            $conditions[] = 'd.branch_id = ?';
            $params[] = (int) $filters['branch_id'];
        }
        if (!empty($filters['status']) && Repair::isValidStatus((string) $filters['status'])) {
            $conditions[] = 'r.status = ?';
            $params[] = (string) $filters['status'];
        } elseif (($filters['scope'] ?? 'open') !== 'all') {
            $conditions[] = 'r.status NOT IN (?, ?)';
            $params[] = Repair::STATUS_PICKED_UP;
            $params[] = Repair::STATUS_CANCELLED;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $from = ' FROM repairs r INNER JOIN documents d ON d.id = r.document_id' . $where;

        $total = (int) $this->connection->fetchOne('SELECT COUNT(*)' . $from, $params);

        $perPage = max(1, $perPage);
        $offset = (max(1, $page) - 1) * $perPage;

        $rows = $this->connection->fetchAllAssociative(
            'SELECT r.*, d.id AS d_id, d.doc_type, d.doc_number, d.branch_id, d.user_id, d.customer_id, '
            . 'd.customer_name, d.customer_phone, d.customer_email, d.customer_street, d.customer_zip, '
            . 'd.customer_city, d.created_at AS d_created_at, d.updated_at AS d_updated_at'
            . $from
            . ' ORDER BY r.due_date IS NULL, r.due_date ASC, d.created_at DESC, r.id DESC'
            . sprintf(' LIMIT %d OFFSET %d', $perPage, $offset),
            $params
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'document' => $this->documents->hydrateRow([
                    'id' => $row['d_id'],
                    'created_at' => $row['d_created_at'],
                    'updated_at' => $row['d_updated_at'],
                ] + $row),
                'repair' => $this->hydrate($row),
            ];
        }

        return ['items' => $items, 'total' => $total];
    }

    public function countOpen(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM repairs WHERE status NOT IN (?, ?)',
            [Repair::STATUS_PICKED_UP, Repair::STATUS_CANCELLED]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM repairs WHERE status = ?', [$status]);
    }

    /**
     * @return list<Repair>
     */
    public function findAll(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM repairs ORDER BY id DESC');

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Repair $repair): array
    {
        return [
            'status' => $repair->getStatus(),
            'device_name' => $repair->getDeviceName(),
            'imei_serial' => $repair->getImeiSerial(),
            'damage_type' => $repair->getDamageType(),
            'issue_text' => $repair->getIssueText(),
            'work_text' => $repair->getWorkText(),
            'price_chf' => $repair->getPriceChf(),
            'due_date' => $repair->getDueDate()?->format('Y-m-d'),
            'accessories' => $repair->getAccessories(),
            'unlock_code' => $repair->getUnlockCode(),
            'closed_at' => $repair->getClosedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(array $row): Repair
    {
        return new Repair(
            id: (int) $row['id'],
            documentId: (int) $row['document_id'],
            status: $row['status'],
            statusToken: $row['status_token'],
            deviceName: $row['device_name'],
            imeiSerial: $row['imei_serial'],
            damageType: $row['damage_type'],
            issueText: $row['issue_text'],
            workText: $row['work_text'],
            priceChf: $row['price_chf'] !== null ? (string) $row['price_chf'] : null,
            dueDate: $row['due_date'] !== null ? new \DateTimeImmutable($row['due_date']) : null,
            accessories: $row['accessories'],
            unlockCode: $row['unlock_code'],
            closedAt: $row['closed_at'] !== null ? new \DateTimeImmutable($row['closed_at']) : null,
            createdAt: isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
            updatedAt: isset($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null
        );
    }
}
