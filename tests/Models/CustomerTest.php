<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\Customer;
use App\Models\CustomerData;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    private function make(
        string $name = 'Max Muster',
        ?string $company = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $street = null,
        ?string $notes = null
    ): Customer {
        return new Customer(1, $name, $company, $phone, $email, $street, null, null, $notes);
    }

    public function testDisplayNameWithCompany(): void
    {
        $this->assertSame('Muster AG, Max Muster', $this->make('Max Muster', 'Muster AG')->getDisplayName());
    }

    public function testDisplayNameCompanyEqualsName(): void
    {
        $this->assertSame('Muster AG', $this->make('Muster AG', 'Muster AG')->getDisplayName());
    }

    public function testWithDataOverwritesOnlyNonNull(): void
    {
        $customer = $this->make(phone: '079 1', email: 'a@b.ch');
        $updated = $customer->withData(new CustomerData('Max Muster', null, '078 2', null));

        $this->assertSame('078 2', $updated->getPhone());
        $this->assertSame('a@b.ch', $updated->getEmail());
        $this->assertSame('079 1', $customer->getPhone());
    }

    public function testPhoneNormalized(): void
    {
        $this->assertSame('0791234567', $this->make(phone: '+41 79 123 45 67')->getPhoneNormalized());
    }

    public function testMergedWithFillsEmptyAndJoinsNotes(): void
    {
        $target = $this->make(notes: 'A');
        $source = $this->make(street: 'X', notes: 'B');

        $merged = $target->mergedWith($source);

        $this->assertSame('X', $merged->getStreet());
        $this->assertSame("A\n\nB", $merged->getNotes());
    }

    public function testMergedWithKeepsTargetValues(): void
    {
        $merged = $this->make(street: 'Ziel')->mergedWith($this->make(street: 'Quelle'));

        $this->assertSame('Ziel', $merged->getStreet());
    }
}
