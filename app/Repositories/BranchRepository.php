<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Branch;
use Doctrine\DBAL\Connection;

class BranchRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM branches ORDER BY name ASC';
        $rows = $this->connection->fetchAllAssociative($sql);
        
        return array_map([$this, 'hydrateBranch'], $rows);
    }

    public function findById(int $id): ?Branch
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM branches WHERE id = ?',
            [$id]
        );

        return $row ? $this->hydrateBranch($row) : null;
    }

    public function create(Branch $branch): int
    {
        $this->connection->insert('branches', [
            'name' => $branch->getName(),
            'street' => $branch->getStreet(),
            'zip' => $branch->getZip(),
            'city' => $branch->getCity(),
            'phone' => $branch->getPhone(),
            'email' => $branch->getEmail(),
            'created_at' => $branch->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $branch->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function hydrateBranch(array $row): Branch
    {
        return new Branch(
            name: $row['name'],
            street: $row['street'],
            zip: $row['zip'],
            city: $row['city'],
            phone: $row['phone'],
            email: $row['email'],
            id: (int) $row['id'],
            createdAt: new \DateTime($row['created_at']),
            updatedAt: new \DateTime($row['updated_at'])
        );
    }
}