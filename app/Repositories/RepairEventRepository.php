<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\RepairEvent;
use Doctrine\DBAL\Connection;

class RepairEventRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function add(RepairEvent $event): void
    {
        $this->connection->insert('repair_events', [
            'repair_id' => $event->getRepairId(),
            'event_type' => $event->getEventType(),
            'status' => $event->getStatus(),
            'message' => self::truncateMessage($event->getMessage()),
            'user_id' => $event->getUserId(),
        ]);
    }

    public const MESSAGE_MAX_LENGTH = 500;

    public static function truncateMessage(?string $message): ?string
    {
        return $message === null ? null : mb_substr($message, 0, self::MESSAGE_MAX_LENGTH);
    }

    /**
     * @return list<RepairEvent> älteste zuerst
     */
    public function findByRepairId(int $repairId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM repair_events WHERE repair_id = ? ORDER BY created_at ASC, id ASC',
            [$repairId]
        );

        return array_map(static fn (array $row): RepairEvent => new RepairEvent(
            id: (int) $row['id'],
            repairId: (int) $row['repair_id'],
            eventType: $row['event_type'],
            status: $row['status'],
            message: $row['message'],
            userId: $row['user_id'] !== null ? (int) $row['user_id'] : null,
            createdAt: new \DateTimeImmutable($row['created_at'])
        ), $rows);
    }
}
