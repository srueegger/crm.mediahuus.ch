<?php

declare(strict_types=1);

namespace App\Models;

final class RepairEvent
{
    public const TYPE_STATUS = 'status';
    public const TYPE_EMAIL_SENT = 'email_sent';
    public const TYPE_EMAIL_FAILED = 'email_failed';
    public const TYPE_NOTE = 'note';

    public function __construct(
        private ?int $id,
        private int $repairId,
        private string $eventType,
        private ?string $status,
        private ?string $message,
        private ?int $userId,
        private ?\DateTimeImmutable $createdAt = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepairId(): int
    {
        return $this->repairId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'repair_id' => $this->repairId,
            'event_type' => $this->eventType,
            'status' => $this->status,
            'message' => $this->message,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
