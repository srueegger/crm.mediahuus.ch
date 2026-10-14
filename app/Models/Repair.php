<?php

declare(strict_types=1);

namespace App\Models;

final class Repair
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_PARTS = 'waiting_parts';
    public const STATUS_READY = 'ready';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_RECEIVED => 'Angenommen',
        self::STATUS_IN_PROGRESS => 'In Arbeit',
        self::STATUS_WAITING_PARTS => 'Wartet auf Ersatzteil',
        self::STATUS_READY => 'Abholbereit',
        self::STATUS_PICKED_UP => 'Abgeholt',
        self::STATUS_CANCELLED => 'Storniert',
    ];

    public const CLOSED_STATUSES = [self::STATUS_PICKED_UP, self::STATUS_CANCELLED];

    public function __construct(
        private ?int $id,
        private int $documentId,
        private string $status,
        private string $statusToken,
        private string $deviceName,
        private ?string $imeiSerial,
        private ?string $damageType,
        private string $issueText,
        private ?string $workText,
        private ?string $priceChf,
        private ?\DateTimeImmutable $dueDate,
        private ?string $accessories,
        private ?string $unlockCode,
        private ?\DateTimeImmutable $closedAt,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException("Ungültiger Reparaturstatus: {$status}");
        }
    }

    public static function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::STATUS_LABELS);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusToken(): string
    {
        return $this->statusToken;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getImeiSerial(): ?string
    {
        return $this->imeiSerial;
    }

    public function getDamageType(): ?string
    {
        return $this->damageType;
    }

    public function getIssueText(): string
    {
        return $this->issueText;
    }

    public function getWorkText(): ?string
    {
        return $this->workText;
    }

    public function getPriceChf(): ?string
    {
        return $this->priceChf;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getAccessories(): ?string
    {
        return $this->accessories;
    }

    public function getUnlockCode(): ?string
    {
        return $this->unlockCode;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOpen(): bool
    {
        return !in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status];
    }

    public function withStatus(string $status, \DateTimeImmutable $now): self
    {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException("Ungültiger Reparaturstatus: {$status}");
        }

        $copy = clone $this;
        $copy->status = $status;
        if (in_array($status, self::CLOSED_STATUSES, true)) {
            $copy->unlockCode = null;
            $copy->closedAt = $now;
        } else {
            $copy->closedAt = null;
        }

        return $copy;
    }

    public function toArray(): array
    {
        $isOverdue = $this->isOpen()
            && $this->status !== self::STATUS_READY
            && $this->dueDate !== null
            && $this->dueDate->format('Y-m-d') < (new \DateTimeImmutable('today'))->format('Y-m-d');

        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'status_token' => $this->statusToken,
            'device_name' => $this->deviceName,
            'imei_serial' => $this->imeiSerial,
            'damage_type' => $this->damageType,
            'issue_text' => $this->issueText,
            'work_text' => $this->workText,
            'price_chf' => $this->priceChf,
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'accessories' => $this->accessories,
            'unlock_code' => $this->unlockCode,
            'closed_at' => $this->closedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'is_open' => $this->isOpen(),
            'is_overdue' => $isOverdue,
        ];
    }
}
