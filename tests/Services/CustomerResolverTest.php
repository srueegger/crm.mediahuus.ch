<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Customer;
use App\Models\CustomerData;
use App\Repositories\CustomerRepository;
use App\Services\CustomerResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerResolverTest extends TestCase
{
    private CustomerRepository|MockObject $repo;
    private CustomerResolver $resolver;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CustomerRepository::class);
        $this->resolver = new CustomerResolver($this->repo);
    }

    private function customer(
        int $id,
        string $name,
        ?string $phone = null,
        ?string $email = null,
        ?string $street = null,
        ?string $zip = null
    ): Customer {
        return new Customer($id, $name, null, $phone, $email, $street, $zip, null, null);
    }

    public function testEmptyNameReturnsNull(): void
    {
        $this->repo->expects($this->never())->method('findById');
        $this->repo->expects($this->never())->method('findCandidates');
        $this->repo->expects($this->never())->method('create');
        $this->repo->expects($this->never())->method('update');

        $this->assertNull($this->resolver->resolve(new CustomerData(''), null));
    }

    public function testSelectedCustomerWithSameNameIsLinked(): void
    {
        $this->repo->method('findById')->with(5)->willReturn($this->customer(5, 'Max Muster'));
        $this->repo->expects($this->never())->method('create');
        $this->repo->expects($this->never())->method('findCandidates');

        $this->assertSame(5, $this->resolver->resolve(new CustomerData('max  muster'), 5));
    }

    public function testSelectedCustomerUpdatedWithNewValues(): void
    {
        $this->repo->method('findById')->with(5)->willReturn($this->customer(5, 'Max Muster', '079 111 22 33'));
        $this->repo->expects($this->once())->method('update')
            ->with($this->callback(fn (Customer $c) => $c->getId() === 5 && $c->getPhone() === '079 999 88 77'));

        $this->assertSame(5, $this->resolver->resolve(new CustomerData('Max Muster', null, '079 999 88 77'), 5));
    }

    public function testSelectedCustomerNotUpdatedWhenUnchanged(): void
    {
        $this->repo->method('findById')->with(5)->willReturn($this->customer(5, 'Max Muster', '079 111 22 33'));
        $this->repo->expects($this->never())->method('update');

        $this->assertSame(5, $this->resolver->resolve(new CustomerData('Max Muster', null, '079 111 22 33'), 5));
    }

    public function testIgnoresSelectionWhenNameChanged(): void
    {
        $this->repo->method('findById')->with(5)->willReturn($this->customer(5, 'Max Muster'));
        $this->repo->method('findCandidates')->willReturn([]);
        $this->repo->expects($this->once())->method('create')->willReturn(9);
        $this->repo->expects($this->never())->method('update');

        $this->assertSame(9, $this->resolver->resolve(new CustomerData('Anna Beispiel'), 5));
    }

    public function testUnknownSelectedIdFallsBackToMatching(): void
    {
        $this->repo->method('findById')->with(77)->willReturn(null);
        $this->repo->expects($this->once())->method('findCandidates')->willReturn([]);
        $this->repo->method('create')->willReturn(10);

        $this->assertSame(10, $this->resolver->resolve(new CustomerData('Max Muster', null, '079 111 22 33'), 77));
    }

    public function testMatchesByPhoneAndName(): void
    {
        $this->repo->method('findCandidates')
            ->willReturn([$this->customer(3, 'Max Muster', '079 111 22 33')]);
        $this->repo->expects($this->never())->method('create');

        $this->assertSame(3, $this->resolver->resolve(new CustomerData('Max Muster', null, '+41 79 111 22 33'), null));
    }

    public function testMatchesByEmailAndName(): void
    {
        $this->repo->method('findCandidates')
            ->willReturn([$this->customer(4, 'Max Muster', null, 'Max@Example.ch')]);
        $this->repo->expects($this->never())->method('create');

        $this->assertSame(4, $this->resolver->resolve(new CustomerData('Max Muster', null, null, 'max@example.ch'), null));
    }

    public function testMatchesByAddressWhenNoPhoneOrEmail(): void
    {
        $this->repo->expects($this->once())->method('findCandidates')
            ->with(null, null, '4153')
            ->willReturn([$this->customer(6, 'Max Muster', null, null, 'hauptstrasse  38', '4153')]);
        $this->repo->expects($this->never())->method('create');

        $data = new CustomerData('Max Muster', null, null, null, 'Hauptstrasse 38', '4153');
        $this->assertSame(6, $this->resolver->resolve($data, null));
    }

    public function testSameZipDifferentStreetNoMatch(): void
    {
        $this->repo->method('findCandidates')
            ->willReturn([$this->customer(6, 'Max Muster', null, null, 'Bahnhofstrasse 1', '4153')]);
        $this->repo->expects($this->once())->method('create')->willReturn(11);

        $data = new CustomerData('Max Muster', null, null, null, 'Hauptstrasse 38', '4153');
        $this->assertSame(11, $this->resolver->resolve($data, null));
    }

    public function testSamePhoneDifferentNameCreatesNewCustomer(): void
    {
        $this->repo->method('findCandidates')
            ->willReturn([$this->customer(3, 'Max Muster', '079 111 22 33')]);
        $this->repo->expects($this->once())->method('create')->willReturn(12);

        $this->assertSame(12, $this->resolver->resolve(new CustomerData('Anna Muster', null, '079 111 22 33'), null));
    }

    public function testMultipleMatchesCreateNewCustomer(): void
    {
        $this->repo->method('findCandidates')->willReturn([
            $this->customer(3, 'Max Muster', '079 111 22 33'),
            $this->customer(4, 'Max Muster', '079 111 22 33'),
        ]);
        $this->repo->expects($this->once())->method('create')->willReturn(13);

        $this->assertSame(13, $this->resolver->resolve(new CustomerData('Max Muster', null, '079 111 22 33'), null));
    }
}
