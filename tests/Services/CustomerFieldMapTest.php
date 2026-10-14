<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Document;
use App\Models\InsuranceAssessment;
use App\Models\Invoice;
use App\Models\Purchase;
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

    public function testRepairMapsNamePhoneEmail(): void
    {
        $this->assertSame([
            'name' => 'customer_name',
            'phone' => 'customer_phone',
            'email' => 'customer_email',
        ], CustomerFieldMap::for(Document::TYPE_REPAIR));
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

    private function doc(string $type, string $name = 'Anna Beispiel'): Document
    {
        return new Document($type, 'X-1', 1, 1, $name, '079 123 45 67', 'anna@example.ch', 5, null, null, 'DocStr 1', '1111', 'DocOrt');
    }

    public function testDataFromPurchaseUsesSellerAddress(): void
    {
        $purchase = new Purchase(5, 'Verkaeufer 2', '4153', 'Reinach', 'Handy', 'Apple', 'iPhone', 100.0, 'front.jpg');

        $data = CustomerFieldMap::dataFromDocument($this->doc(Document::TYPE_PURCHASE), $purchase, null, null);

        $this->assertSame('Anna Beispiel', $data->name);
        $this->assertSame('079 123 45 67', $data->phone);
        $this->assertSame('anna@example.ch', $data->email);
        $this->assertSame('Verkaeufer 2', $data->street);
        $this->assertSame('4153', $data->zip);
        $this->assertSame('Reinach', $data->city);
    }

    public function testDataFromInsuranceUsesAssessmentAddress(): void
    {
        $assessment = new InsuranceAssessment(5, 'display', 'Phone', 'SN', 'Schaden', 'Ergebnis', 'Gutachtenweg 3', '4052', 'Basel');

        $data = CustomerFieldMap::dataFromDocument($this->doc(Document::TYPE_INSURANCE), null, $assessment, null);

        $this->assertSame('anna@example.ch', $data->email);
        $this->assertSame('Gutachtenweg 3', $data->street);
        $this->assertSame('4052', $data->zip);
        $this->assertSame('Basel', $data->city);
    }

    private function invoice(?string $company, ?string $contact): Invoice
    {
        return new Invoice(
            5,
            $company,
            $contact,
            new \DateTimeImmutable('2026-01-01'),
            10,
            new \DateTimeImmutable('2026-01-11'),
            '10.00',
            '0.81',
            '10.81',
            'RF00'
        );
    }

    public function testDataFromInvoiceUsesContactThenCompany(): void
    {
        $doc = $this->doc(Document::TYPE_INVOICE, 'Muster AG');

        $withContact = CustomerFieldMap::dataFromDocument($doc, null, null, $this->invoice('Muster AG', 'Hans Muster'));
        $this->assertSame('Hans Muster', $withContact->name);
        $this->assertSame('Muster AG', $withContact->company);
        $this->assertSame('DocStr 1', $withContact->street);
        $this->assertSame('1111', $withContact->zip);
        $this->assertSame('DocOrt', $withContact->city);

        $companyOnly = CustomerFieldMap::dataFromDocument($doc, null, null, $this->invoice('Muster AG', null));
        $this->assertSame('Muster AG', $companyOnly->name);
        $this->assertSame('Muster AG', $companyOnly->company);
    }

    public function testIsAnonymousReceiptName(): void
    {
        $this->assertTrue(CustomerFieldMap::isAnonymousReceiptName(' BarKauf '));
        $this->assertFalse(CustomerFieldMap::isAnonymousReceiptName('Barkauf AG'));
        $this->assertFalse(CustomerFieldMap::isAnonymousReceiptName(''));
    }

    public function testBarkaufReceiptIsEmpty(): void
    {
        $data = CustomerFieldMap::dataFromDocument($this->doc(Document::TYPE_RECEIPT, '  barkauf '), null, null, null);

        $this->assertTrue($data->isEmpty());
    }

    public function testReceiptWithNameKeepsNameAndAddress(): void
    {
        $data = CustomerFieldMap::dataFromDocument($this->doc(Document::TYPE_RECEIPT), null, null, null);

        $this->assertSame('Anna Beispiel', $data->name);
        $this->assertSame('DocStr 1', $data->street);
        $this->assertNull($data->phone);
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerFieldMap::for('foo');
    }
}
