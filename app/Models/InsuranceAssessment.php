<?php
declare(strict_types=1);

namespace App\Models;

class InsuranceAssessment
{
    public const RESULT_TOTAL_LOSS = 'total_loss';
    public const RESULT_REPAIR_RECOMMENDED = 'repair_recommended';
    public const RESULT_NOT_REPAIRABLE = 'not_repairable';

    private ?int $id;
    private int $documentId;
    private string $damageType;
    private string $deviceName;
    private string $serialNumber;
    private string $damageDescription;
    private string $assessmentResult;
    private float $deviceValueChf;
    private float $repairCostChf;

    public function __construct(
        int $documentId,
        string $damageType,
        string $deviceName,
        string $serialNumber,
        string $damageDescription,
        string $assessmentResult,
        float $deviceValueChf,
        float $repairCostChf,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->documentId = $documentId;
        $this->damageType = $damageType;
        $this->deviceName = $deviceName;
        $this->serialNumber = $serialNumber;
        $this->damageDescription = $damageDescription;
        $this->assessmentResult = $assessmentResult;
        $this->deviceValueChf = $deviceValueChf;
        $this->repairCostChf = $repairCostChf;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getDamageType(): string
    {
        return $this->damageType;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function getDamageDescription(): string
    {
        return $this->damageDescription;
    }

    public function getAssessmentResult(): string
    {
        return $this->assessmentResult;
    }

    public function getAssessmentResultLabel(): string
    {
        return match ($this->assessmentResult) {
            self::RESULT_TOTAL_LOSS => 'Totalschaden',
            self::RESULT_REPAIR_RECOMMENDED => 'Reparatur empfohlen',
            self::RESULT_NOT_REPAIRABLE => 'Nicht reparierbar',
            default => $this->assessmentResult,
        };
    }

    public function getDeviceValueChf(): float
    {
        return $this->deviceValueChf;
    }

    public function getFormattedDeviceValue(): string
    {
        return 'CHF ' . number_format($this->deviceValueChf, 2, '.', "'");
    }

    public function getRepairCostChf(): float
    {
        return $this->repairCostChf;
    }

    public function getFormattedRepairCost(): string
    {
        return 'CHF ' . number_format($this->repairCostChf, 2, '.', "'");
    }

    public static function getResultOptions(): array
    {
        return [
            self::RESULT_TOTAL_LOSS => 'Totalschaden',
            self::RESULT_REPAIR_RECOMMENDED => 'Reparatur empfohlen',
            self::RESULT_NOT_REPAIRABLE => 'Nicht reparierbar',
        ];
    }

    public static function isValidResult(string $result): bool
    {
        return in_array($result, [self::RESULT_TOTAL_LOSS, self::RESULT_REPAIR_RECOMMENDED, self::RESULT_NOT_REPAIRABLE], true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'damage_type' => $this->damageType,
            'device_name' => $this->deviceName,
            'serial_number' => $this->serialNumber,
            'damage_description' => $this->damageDescription,
            'assessment_result' => $this->assessmentResult,
            'assessment_result_label' => $this->getAssessmentResultLabel(),
            'device_value_chf' => $this->deviceValueChf,
            'formatted_device_value' => $this->getFormattedDeviceValue(),
            'repair_cost_chf' => $this->repairCostChf,
            'formatted_repair_cost' => $this->getFormattedRepairCost(),
        ];
    }
}
