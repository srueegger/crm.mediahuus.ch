<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\CustomerData;
use PHPUnit\Framework\TestCase;

class CustomerDataTest extends TestCase
{
    public function testTrimsAndNullsEmptyFields(): void
    {
        $customer = new CustomerData('  Max ', null, ' ', '');

        $this->assertSame('Max', $customer->name);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->email);
        $this->assertNull($customer->company);
    }

    public function testNormalizeName(): void
    {
        $this->assertSame('max muster', CustomerData::normalizeName('  Max   MUSTER '));
        $this->assertSame('', CustomerData::normalizeName('   '));
    }

    public function testNormalizeStreet(): void
    {
        $this->assertSame('hauptstrasse 38', CustomerData::normalizeStreet('Hauptstrasse  38 '));
        $this->assertNull(CustomerData::normalizeStreet(null));
        $this->assertNull(CustomerData::normalizeStreet(' '));
    }

    public function testNormalizeEmail(): void
    {
        $this->assertSame('max@example.ch', CustomerData::normalizeEmail(' Max@Example.CH '));
        $this->assertNull(CustomerData::normalizeEmail(''));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new CustomerData(''))->isEmpty());
        $this->assertFalse((new CustomerData('Max'))->isEmpty());
    }
}
