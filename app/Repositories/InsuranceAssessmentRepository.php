<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\InsuranceAssessment;
use Doctrine\DBAL\Connection;

class InsuranceAssessmentRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(InsuranceAssessment $assessment): int
    {
        $this->connection->insert('insurance_assessments', [
            'document_id' => $assessment->getDocumentId(),
            'damage_type' => $assessment->getDamageType(),
            'device_name' => $assessment->getDeviceName(),
            'serial_number' => $assessment->getSerialNumber(),
            'damage_description' => $assessment->getDamageDescription(),
            'assessment_result' => $assessment->getAssessmentResult(),
            'device_value_chf' => $assessment->getDeviceValueChf(),
            'repair_cost_chf' => $assessment->getRepairCostChf(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?InsuranceAssessment
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM insurance_assessments WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateInsuranceAssessment($row) : null;
    }

    public function findByDocumentId(int $documentId): ?InsuranceAssessment
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM insurance_assessments WHERE document_id = ?',
            [$documentId]
        );

        return $row ? $this->hydrateInsuranceAssessment($row) : null;
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM insurance_assessments ORDER BY id DESC';
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map([$this, 'hydrateInsuranceAssessment'], $rows);
    }

    public function update(InsuranceAssessment $assessment): bool
    {
        if (!$assessment->getId()) {
            throw new \InvalidArgumentException('InsuranceAssessment ID is required for update');
        }

        $affectedRows = $this->connection->update('insurance_assessments', [
            'damage_type' => $assessment->getDamageType(),
            'device_name' => $assessment->getDeviceName(),
            'serial_number' => $assessment->getSerialNumber(),
            'damage_description' => $assessment->getDamageDescription(),
            'assessment_result' => $assessment->getAssessmentResult(),
            'device_value_chf' => $assessment->getDeviceValueChf(),
            'repair_cost_chf' => $assessment->getRepairCostChf(),
        ], [
            'id' => $assessment->getId()
        ]);

        return $affectedRows > 0;
    }

    private function hydrateInsuranceAssessment(array $row): InsuranceAssessment
    {
        return new InsuranceAssessment(
            documentId: (int) $row['document_id'],
            damageType: $row['damage_type'] ?? 'other',
            deviceName: $row['device_name'],
            serialNumber: $row['serial_number'],
            damageDescription: $row['damage_description'] ?? '',
            assessmentResult: $row['assessment_result'] ?? 'total_loss',
            deviceValueChf: $row['device_value_chf'] !== null ? (float) $row['device_value_chf'] : null,
            repairCostChf: $row['repair_cost_chf'] !== null ? (float) $row['repair_cost_chf'] : null,
            id: (int) $row['id']
        );
    }
}
