<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Purchase;
use Doctrine\DBAL\Connection;

class PurchaseRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Purchase $purchase): int
    {
        $this->connection->insert('purchases', [
            'document_id' => $purchase->getDocumentId(),
            'seller_street' => $purchase->getSellerStreet(),
            'seller_zip' => $purchase->getSellerZip(),
            'seller_city' => $purchase->getSellerCity(),
            'device_type' => $purchase->getDeviceType(),
            'device_brand' => $purchase->getDeviceBrand(),
            'device_model' => $purchase->getDeviceModel(),
            'imei' => $purchase->getImei(),
            'serial_number' => $purchase->getSerialNumber(),
            'device_condition' => $purchase->getDeviceCondition(),
            'accessories' => $purchase->getAccessories(),
            'purchase_price_chf' => $purchase->getPurchasePriceChf(),
            'id_document_front' => $purchase->getIdDocumentFront(),
            'id_document_back' => $purchase->getIdDocumentBack(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $id): ?Purchase
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM purchases WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydratePurchase($row) : null;
    }

    public function findByDocumentId(int $documentId): ?Purchase
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM purchases WHERE document_id = ?',
            [$documentId]
        );

        return $row ? $this->hydratePurchase($row) : null;
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM purchases ORDER BY id DESC';
        $rows = $this->connection->fetchAllAssociative($sql);

        return array_map([$this, 'hydratePurchase'], $rows);
    }

    public function update(Purchase $purchase): bool
    {
        if (!$purchase->getId()) {
            throw new \InvalidArgumentException('Purchase ID is required for update');
        }

        $affectedRows = $this->connection->update('purchases', [
            'seller_street' => $purchase->getSellerStreet(),
            'seller_zip' => $purchase->getSellerZip(),
            'seller_city' => $purchase->getSellerCity(),
            'device_type' => $purchase->getDeviceType(),
            'device_brand' => $purchase->getDeviceBrand(),
            'device_model' => $purchase->getDeviceModel(),
            'imei' => $purchase->getImei(),
            'serial_number' => $purchase->getSerialNumber(),
            'device_condition' => $purchase->getDeviceCondition(),
            'accessories' => $purchase->getAccessories(),
            'purchase_price_chf' => $purchase->getPurchasePriceChf(),
            'id_document_front' => $purchase->getIdDocumentFront(),
            'id_document_back' => $purchase->getIdDocumentBack(),
        ], [
            'id' => $purchase->getId()
        ]);

        return $affectedRows > 0;
    }

    public function delete(int $id): bool
    {
        $affectedRows = $this->connection->delete('purchases', ['id' => $id]);
        return $affectedRows > 0;
    }

    private function hydratePurchase(array $row): Purchase
    {
        return new Purchase(
            documentId: (int) $row['document_id'],
            sellerStreet: $row['seller_street'],
            sellerZip: $row['seller_zip'],
            sellerCity: $row['seller_city'],
            deviceType: $row['device_type'],
            deviceBrand: $row['device_brand'],
            deviceModel: $row['device_model'],
            purchasePriceChf: (float) $row['purchase_price_chf'],
            idDocumentFront: $row['id_document_front'],
            imei: $row['imei'] ?? null,
            serialNumber: $row['serial_number'] ?? null,
            deviceCondition: $row['device_condition'] ?? null,
            accessories: $row['accessories'] ?? null,
            idDocumentBack: $row['id_document_back'] ?? null,
            id: (int) $row['id']
        );
    }
}
