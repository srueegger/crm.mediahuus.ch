<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Repositories\DocumentRepository;
use Doctrine\DBAL\Connection;

class CustomerMergeService
{
    public function __construct(
        private Connection $connection,
        private CustomerRepository $customers,
        private DocumentRepository $documents
    ) {
    }

    /**
     * Führt den Quell-Kunden in den Ziel-Kunden zusammen: Dokumente wandern zum Ziel,
     * leere Zielfelder werden ergänzt, die Quelle wird gelöscht.
     *
     * @throws \InvalidArgumentException bei gleicher ID oder unbekanntem Kunden
     */
    public function merge(int $sourceId, int $targetId): void
    {
        if ($sourceId === $targetId) {
            throw new \InvalidArgumentException('Quelle und Ziel müssen verschieden sein.');
        }

        $this->connection->transactional(function () use ($sourceId, $targetId): void {
            $source = $this->customers->findById($sourceId);
            $target = $this->customers->findById($targetId);
            if ($source === null || $target === null) {
                throw new \InvalidArgumentException('Kunde nicht gefunden.');
            }

            // Zuerst umhängen: ON DELETE SET NULL würde die Dokumente sonst vom Kunden lösen.
            $this->documents->reassignCustomer($sourceId, $targetId);
            $this->customers->update($target->mergedWith($source));
            $this->customers->delete($sourceId);
        });
    }
}
