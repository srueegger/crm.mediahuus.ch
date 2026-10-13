<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Repositories\CustomerRepository;
use App\Services\CustomerFormPrefill;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerFormPrefillTest extends TestCase
{
    private CustomerRepository|MockObject $repo;
    private CustomerFormPrefill $prefill;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CustomerRepository::class);
        $this->repo->method('findById')->willReturnCallback(
            fn (int $id) => match ($id) {
                7 => new Customer(7, 'Anna Beispiel', null, '079 123 45 67', 'anna@example.ch', 'Weg 1', '4153', 'Reinach', null),
                8 => new Customer(8, 'Muster AG', 'Muster AG', null, null, 'Gasse 2', '4052', 'Basel', null),
                9 => new Customer(9, 'Hans Muster', 'Muster AG', null, null, null, null, null, null),
                default => null,
            }
        );
        $this->prefill = new CustomerFormPrefill($this->repo);
    }

    public function testInvalidIdYieldsEmptyPrefill(): void
    {
        foreach (['abc', '0', null, 99, '99', '-1', '7x', 1.5, []] as $param) {
            $this->assertSame(
                ['formData' => [], 'linkedCustomer' => null],
                $this->prefill->forRequest(Document::TYPE_INSURANCE, $param),
                var_export($param, true)
            );
        }
    }

    public function testPrefillsInsuranceFields(): void
    {
        $result = $this->prefill->forRequest(Document::TYPE_INSURANCE, '7');

        $this->assertSame('Anna Beispiel', $result['formData']['customer_name']);
        $this->assertSame('Weg 1', $result['formData']['customer_street']);
        $this->assertSame('4153', $result['formData']['customer_zip']);
        $this->assertSame('7', $result['formData']['customer_id']);
        $this->assertSame(7, $result['linkedCustomer']['id']);
    }

    public function testNullAttributesAreOmitted(): void
    {
        $result = $this->prefill->forRequest(Document::TYPE_ESTIMATE, 8);

        $this->assertArrayNotHasKey('customer_phone', $result['formData']);
        $this->assertSame('8', $result['formData']['customer_id']);
    }

    public function testInvoiceCompanyOnlyLeavesContactEmpty(): void
    {
        $formData = $this->prefill->forRequest(Document::TYPE_INVOICE, '8')['formData'];

        $this->assertSame('Muster AG', $formData['company_name']);
        $this->assertArrayNotHasKey('contact_name', $formData);
        $this->assertSame('Gasse 2', $formData['street']);
    }

    public function testInvoiceWithContactName(): void
    {
        $formData = $this->prefill->forRequest(Document::TYPE_INVOICE, '9')['formData'];

        $this->assertSame('Hans Muster', $formData['contact_name']);
        $this->assertSame('Muster AG', $formData['company_name']);
    }

    public function testLinked(): void
    {
        $this->assertSame(7, $this->prefill->linked('7')['id']);
        $this->assertNull($this->prefill->linked('abc'));
        $this->assertNull($this->prefill->linked(99));
    }
}
