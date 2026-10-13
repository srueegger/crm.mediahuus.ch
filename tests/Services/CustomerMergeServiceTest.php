<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Repositories\DocumentRepository;
use App\Services\CustomerMergeService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerMergeServiceTest extends TestCase
{
    private Connection|MockObject $connection;
    private CustomerRepository|MockObject $customers;
    private DocumentRepository|MockObject $documents;
    private CustomerMergeService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->method('transactional')
            ->willReturnCallback(fn(\Closure $callback) => $callback($this->connection));
        $this->customers = $this->createMock(CustomerRepository::class);
        $this->documents = $this->createMock(DocumentRepository::class);
        $this->service = new CustomerMergeService($this->connection, $this->customers, $this->documents);
    }

    private function customer(int $id, string $name, ?string $phone, ?string $notes): Customer
    {
        return new Customer($id, $name, null, $phone, null, null, null, null, $notes);
    }

    public function testMergeReassignsUpdatesAndDeletes(): void
    {
        $source = $this->customer(3, 'Quelle', '079 111 22 33', 'Notiz Quelle');
        $target = $this->customer(7, 'Ziel', null, 'Notiz Ziel');
        $this->customers->method('findById')->willReturnMap([[3, $source], [7, $target]]);

        $calls = [];
        $this->documents->expects($this->once())->method('reassignCustomer')->with(3, 7)
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'reassign';

                return 2;
            });
        $this->customers->expects($this->once())->method('update')
            ->with($this->callback(function (Customer $c) use (&$calls): bool {
                $calls[] = 'update';

                return $c->getId() === 7
                    && $c->getName() === 'Ziel'
                    && $c->getPhone() === '079 111 22 33'
                    && $c->getNotes() === "Notiz Ziel\n\nNotiz Quelle";
            }));
        $this->customers->expects($this->once())->method('delete')->with(3)
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'delete';
            });

        $this->service->merge(3, 7);

        $this->assertSame(['reassign', 'update', 'delete'], $calls);
    }

    public function testSameIdThrows(): void
    {
        $this->customers->expects($this->never())->method('update');
        $this->customers->expects($this->never())->method('delete');
        $this->documents->expects($this->never())->method('reassignCustomer');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->merge(3, 3);
    }

    public function testMissingCustomerThrows(): void
    {
        $this->customers->method('findById')->willReturnMap([
            [3, $this->customer(3, 'Quelle', null, null)],
            [7, null],
        ]);
        $this->customers->expects($this->never())->method('update');
        $this->customers->expects($this->never())->method('delete');
        $this->documents->expects($this->never())->method('reassignCustomer');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->merge(3, 7);
    }
}
