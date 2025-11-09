<?php
declare(strict_types=1);

namespace App\Models;

class Purchase
{
    private ?int $id;
    private int $documentId;
    private string $sellerStreet;
    private string $sellerZip;
    private string $sellerCity;
    private string $deviceType;
    private string $deviceBrand;
    private string $deviceModel;
    private ?string $imei;
    private ?string $serialNumber;
    private ?string $deviceCondition;
    private ?string $accessories;
    private float $purchasePriceChf;
    private string $idDocumentFront;
    private ?string $idDocumentBack;

    public function __construct(
        int $documentId,
        string $sellerStreet,
        string $sellerZip,
        string $sellerCity,
        string $deviceType,
        string $deviceBrand,
        string $deviceModel,
        float $purchasePriceChf,
        string $idDocumentFront,
        ?string $imei = null,
        ?string $serialNumber = null,
        ?string $deviceCondition = null,
        ?string $accessories = null,
        ?string $idDocumentBack = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->documentId = $documentId;
        $this->sellerStreet = $sellerStreet;
        $this->sellerZip = $sellerZip;
        $this->sellerCity = $sellerCity;
        $this->deviceType = $deviceType;
        $this->deviceBrand = $deviceBrand;
        $this->deviceModel = $deviceModel;
        $this->imei = $imei;
        $this->serialNumber = $serialNumber;
        $this->deviceCondition = $deviceCondition;
        $this->accessories = $accessories;
        $this->purchasePriceChf = $purchasePriceChf;
        $this->idDocumentFront = $idDocumentFront;
        $this->idDocumentBack = $idDocumentBack;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getSellerStreet(): string
    {
        return $this->sellerStreet;
    }

    public function getSellerZip(): string
    {
        return $this->sellerZip;
    }

    public function getSellerCity(): string
    {
        return $this->sellerCity;
    }

    public function getSellerAddress(): string
    {
        return $this->sellerStreet . ', ' . $this->sellerZip . ' ' . $this->sellerCity;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getDeviceTypeName(): string
    {
        return match($this->deviceType) {
            'smartphone' => 'Smartphone',
            'tablet' => 'Tablet',
            'watch' => 'Apple Watch',
            'other' => 'Sonstiges',
            default => $this->deviceType,
        };
    }

    public function getDeviceBrand(): string
    {
        return $this->deviceBrand;
    }

    public function getDeviceModel(): string
    {
        return $this->deviceModel;
    }

    public function getDeviceFullName(): string
    {
        return $this->deviceBrand . ' ' . $this->deviceModel;
    }

    public function getImei(): ?string
    {
        return $this->imei;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getDeviceCondition(): ?string
    {
        return $this->deviceCondition;
    }

    public function getAccessories(): ?string
    {
        return $this->accessories;
    }

    public function getPurchasePriceChf(): float
    {
        return $this->purchasePriceChf;
    }

    public function getFormattedPrice(): string
    {
        return 'CHF ' . number_format($this->purchasePriceChf, 2, '.', "'");
    }

    public function getIdDocumentFront(): string
    {
        return $this->idDocumentFront;
    }

    public function getIdDocumentBack(): ?string
    {
        return $this->idDocumentBack;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->documentId,
            'seller_street' => $this->sellerStreet,
            'seller_zip' => $this->sellerZip,
            'seller_city' => $this->sellerCity,
            'seller_address' => $this->getSellerAddress(),
            'device_type' => $this->deviceType,
            'device_type_name' => $this->getDeviceTypeName(),
            'device_brand' => $this->deviceBrand,
            'device_model' => $this->deviceModel,
            'device_full_name' => $this->getDeviceFullName(),
            'imei' => $this->imei,
            'serial_number' => $this->serialNumber,
            'device_condition' => $this->deviceCondition,
            'accessories' => $this->accessories,
            'purchase_price_chf' => $this->purchasePriceChf,
            'formatted_price' => $this->getFormattedPrice(),
            'id_document_front' => $this->idDocumentFront,
            'id_document_back' => $this->idDocumentBack,
        ];
    }
}
