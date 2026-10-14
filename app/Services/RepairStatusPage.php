<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;

/**
 * Reduziert eine Reparatur auf die Felder der öffentlichen Statusseite.
 * Das Template erhält ausschliesslich dieses Array, nie Kundendaten oder interne Notizen.
 */
class RepairStatusPage
{
    private const STEPS = [
        Repair::STATUS_RECEIVED,
        Repair::STATUS_IN_PROGRESS,
        Repair::STATUS_WAITING_PARTS,
        Repair::STATUS_READY,
        Repair::STATUS_PICKED_UP,
    ];

    public function isExpired(Repair $repair, \DateTimeImmutable $now): bool
    {
        $closedAt = $repair->getClosedAt();

        return $closedAt !== null && $closedAt < $now->modify('-30 days');
    }

    /**
     * @param list<RepairEvent> $events
     */
    public function build(Document $document, Repair $repair, array $events, Branch $branch): array
    {
        $status = $repair->getStatus();
        $currentIndex = array_search($status, self::STEPS, true);

        $steps = [];
        foreach (self::STEPS as $index => $step) {
            $steps[] = [
                'label' => Repair::STATUS_LABELS[$step],
                'done' => $currentIndex !== false && $index <= $currentIndex,
                'current' => $currentIndex !== false && $index === $currentIndex,
            ];
        }

        $history = [];
        foreach ($events as $event) {
            if ($event->getEventType() !== RepairEvent::TYPE_STATUS || $event->getStatus() === null) {
                continue;
            }
            $history[] = [
                'status_label' => Repair::STATUS_LABELS[$event->getStatus()] ?? $event->getStatus(),
                'date' => $event->getCreatedAt()?->format('d.m.Y H:i') ?? '',
            ];
        }

        $price = $repair->getPriceChf();

        return [
            'doc_number' => $document->getDocNumber(),
            'device_name' => $repair->getDeviceName(),
            'status' => $status,
            'status_label' => Repair::STATUS_LABELS[$status] ?? $status,
            'cancelled' => $status === Repair::STATUS_CANCELLED,
            'steps' => $steps,
            'history' => $history,
            'due_date' => $repair->getDueDate()?->format('d.m.Y'),
            'price_chf' => $price !== null && $price !== '' ? 'CHF ' . number_format((float) $price, 2, '.', '') : null,
            'branch' => [
                'name' => $branch->getName(),
                'street' => $branch->getStreet(),
                'zip' => $branch->getZip(),
                'city' => $branch->getCity(),
                'phone' => $branch->getPhone(),
                'email' => $branch->getEmail(),
            ],
        ];
    }
}
