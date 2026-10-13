<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Document;
use App\Services\CustomerFieldMap;
use PHPUnit\Framework\TestCase;

class CustomerFieldMapTest extends TestCase
{
    public function testPurchaseMapsSellerAddress(): void
    {
        $map = CustomerFieldMap::for(Document::TYPE_PURCHASE);

        $this->assertSame('seller_street', $map['street']);
        $this->assertSame('seller_zip', $map['zip']);
        $this->assertSame('seller_city', $map['city']);
    }

    public function testReceiptHasNoPhone(): void
    {
        $map = CustomerFieldMap::for(Document::TYPE_RECEIPT);

        $this->assertArrayNotHasKey('phone', $map);
        $this->assertArrayNotHasKey('email', $map);
        $this->assertSame('customer_street', $map['street']);
    }

    public function testInvoiceNameFallsBackToCompany(): void
    {
        $data = CustomerFieldMap::dataFromForm(Document::TYPE_INVOICE, [
            'company_name' => 'Muster AG',
            'contact_name' => '',
        ]);

        $this->assertSame('Muster AG', $data->name);
        $this->assertSame('Muster AG', $data->company);
    }

    public function testInvoiceUsesContactName(): void
    {
        $data = CustomerFieldMap::dataFromForm(Document::TYPE_INVOICE, [
            'company_name' => 'Muster AG',
            'contact_name' => 'Hans Muster',
            'street' => 'Weg 1',
        ]);

        $this->assertSame('Hans Muster', $data->name);
        $this->assertSame('Weg 1', $data->street);
    }

    public function testEstimateData(): void
    {
        $data = CustomerFieldMap::dataFromForm(Document::TYPE_ESTIMATE, [
            'customer_name' => 'Anna Beispiel',
            'customer_phone' => '079 123 45 67',
            'customer_email' => 'anna@example.ch',
            'customer_street' => 'ignored',
        ]);

        $this->assertSame('Anna Beispiel', $data->name);
        $this->assertSame('079 123 45 67', $data->phone);
        $this->assertSame('anna@example.ch', $data->email);
        $this->assertNull($data->street);
    }

    public function testNonStringValuesAreMissing(): void
    {
        $data = CustomerFieldMap::dataFromForm(Document::TYPE_ESTIMATE, [
            'customer_name' => ['x'],
        ]);

        $this->assertSame('', $data->name);
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerFieldMap::for('foo');
    }
}
