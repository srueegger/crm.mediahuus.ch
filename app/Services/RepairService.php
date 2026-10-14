<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Repair;
use App\Models\RepairEvent;
use App\Repositories\BranchRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\RepairEventRepository;
use App\Repositories\RepairRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RepairService
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private Connection $connection,
        private DocumentRepository $documents,
        private RepairRepository $repairs,
        private RepairEventRepository $events,
        private BranchRepository $branches,
        private CustomerResolver $customerResolver,
        private RepairMailer $mailer,
        private RepairStatusTokenGenerator $tokens
    ) {
    }

    /**
     * @param array<string, mixed> $form bereits mit RepairInputValidator geprüft
     */
    public function create(array $form, ?int $selectedCustomerId, int $userId): RepairSaveResult
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                [$document, $repair] = $this->connection->transactional(
                    function () use ($form, $selectedCustomerId, $userId): array {
                        $customerId = $this->customerResolver->resolve(
                            CustomerFieldMap::dataFromForm(Document::TYPE_REPAIR, $form),
                            $selectedCustomerId
                        );

                        $document = new Document(
                            Document::TYPE_REPAIR,
                            $this->documents->generateDocNumber(Document::TYPE_REPAIR),
                            (int) $form['branch_id'],
                            $userId,
                            trim((string) $form['customer_name']),
                            $this->text($form, 'customer_phone'),
                            $this->text($form, 'customer_email'),
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            $customerId
                        );
                        $documentId = $this->documents->create($document);
                        $document = new Document(
                            $document->getDocType(),
                            $document->getDocNumber(),
                            $document->getBranchId(),
                            $userId,
                            $document->getCustomerName(),
                            $document->getCustomerPhone(),
                            $document->getCustomerEmail(),
                            $documentId,
                            null,
                            null,
                            null,
                            null,
                            null,
                            $customerId
                        );

                        $repair = $this->buildRepair(
                            $form,
                            $documentId,
                            Repair::STATUS_RECEIVED,
                            $this->tokens->generate(),
                            null,
                            $this->text($form, 'unlock_code'),
                            null
                        );
                        $repairId = $this->repairs->create($repair);
                        $repair = $this->buildRepair(
                            $form,
                            $documentId,
                            Repair::STATUS_RECEIVED,
                            $repair->getStatusToken(),
                            null,
                            $repair->getUnlockCode(),
                            $repairId
                        );

                        $this->events->add(new RepairEvent(
                            null,
                            $repairId,
                            RepairEvent::TYPE_STATUS,
                            Repair::STATUS_RECEIVED,
                            null,
                            $userId
                        ));

                        return [$document, $repair];
                    }
                );
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw new \RuntimeException('Reparatur konnte nicht angelegt werden (Nummernkollision).', 0, $e);
                }
            }
        }

        $branch = $this->branches->findById($document->getBranchId());
        $mail = $branch !== null ? $this->mailer->sendCreated($document, $repair, $branch, $userId) : null;

        return new RepairSaveResult((int) $document->getId(), $mail);
    }

    /**
     * @param array<string, mixed> $form
     */
    public function update(int $documentId, array $form, ?int $selectedCustomerId, int $userId): void
    {
        $document = $this->documents->findById($documentId);
        $repair = $this->repairs->findByDocumentId($documentId);
        if ($document === null || $repair === null) {
            throw new \RuntimeException('Reparatur nicht gefunden.');
        }

        $this->connection->transactional(function () use ($form, $selectedCustomerId, $userId, $document, $repair, $documentId): void {
            $customerId = $this->customerResolver->resolve(
                CustomerFieldMap::dataFromForm(Document::TYPE_REPAIR, $form),
                $selectedCustomerId
            );

            $this->documents->update(new Document(
                Document::TYPE_REPAIR,
                $document->getDocNumber(),
                (int) $form['branch_id'],
                $document->getUserId(),
                trim((string) $form['customer_name']),
                $this->text($form, 'customer_phone'),
                $this->text($form, 'customer_email'),
                $documentId
            ));
            if ($customerId !== null) {
                $this->documents->setCustomerId($documentId, $customerId);
            }

            $closed = !$repair->isOpen();
            $this->repairs->update($this->buildRepair(
                $form,
                $documentId,
                $repair->getStatus(),
                $repair->getStatusToken(),
                $repair->getClosedAt(),
                $closed ? null : $this->text($form, 'unlock_code'),
                $repair->getId()
            ));

            $this->events->add(new RepairEvent(
                null,
                (int) $repair->getId(),
                RepairEvent::TYPE_NOTE,
                null,
                'Angaben bearbeitet',
                $userId
            ));
        });
    }

    public function changeStatus(int $documentId, string $status, ?string $note, int $userId): ?MailResult
    {
        if (!Repair::isValidStatus($status)) {
            throw new \InvalidArgumentException("Ungültiger Reparaturstatus: {$status}");
        }

        $document = $this->documents->findById($documentId);
        $repair = $this->repairs->findByDocumentId($documentId);
        if ($document === null || $repair === null) {
            throw new \RuntimeException('Reparatur nicht gefunden.');
        }
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;
        if ($repair->getStatus() === $status) {
            if ($note !== null) {
                $this->events->add(new RepairEvent(null, (int) $repair->getId(), RepairEvent::TYPE_NOTE, null, $note, $userId));
            }

            return null;
        }
        $updated = $repair->withStatus($status, new \DateTimeImmutable());

        $this->connection->transactional(function () use ($repair, $updated, $status, $note, $userId): void {
            $this->events->add(new RepairEvent(
                null,
                (int) $repair->getId(),
                RepairEvent::TYPE_STATUS,
                $status,
                $note,
                $userId
            ));
            $this->repairs->update($updated);
        });

        if ($status !== Repair::STATUS_READY) {
            return null;
        }

        $branch = $this->branches->findById($document->getBranchId());

        return $branch !== null ? $this->mailer->sendReady($document, $updated, $branch, $userId) : null;
    }

    public function resend(int $documentId, string $type, int $userId): ?MailResult
    {
        if ($type !== 'created' && $type !== 'ready') {
            throw new \InvalidArgumentException("Ungültiger E-Mail-Typ: {$type}");
        }

        $document = $this->documents->findById($documentId);
        $repair = $this->repairs->findByDocumentId($documentId);
        if ($document === null || $repair === null) {
            throw new \RuntimeException('Reparatur nicht gefunden.');
        }
        $branch = $this->branches->findById($document->getBranchId());
        if ($branch === null) {
            return null;
        }

        return $type === 'created'
            ? $this->mailer->sendCreated($document, $repair, $branch, $userId)
            : $this->mailer->sendReady($document, $repair, $branch, $userId);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function buildRepair(
        array $form,
        int $documentId,
        string $status,
        string $token,
        ?\DateTimeImmutable $closedAt,
        ?string $unlockCode,
        ?int $id
    ): Repair {
        $price = $this->text($form, 'price_chf');
        $due = $this->text($form, 'due_date');

        return new Repair(
            id: $id,
            documentId: $documentId,
            status: $status,
            statusToken: $token,
            deviceName: trim((string) $form['device_name']),
            imeiSerial: $this->text($form, 'imei_serial'),
            damageType: $this->text($form, 'damage_type'),
            issueText: trim((string) $form['issue_text']),
            workText: $this->text($form, 'work_text'),
            priceChf: $price !== null ? number_format((float) str_replace(',', '.', $price), 2, '.', '') : null,
            dueDate: $due !== null ? new \DateTimeImmutable($due) : null,
            accessories: $this->text($form, 'accessories'),
            unlockCode: $unlockCode,
            closedAt: $closedAt
        );
    }

    /**
     * @param array<string, mixed> $form
     */
    private function text(array $form, string $key): ?string
    {
        $value = $form[$key] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
