<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Doctrine\DBAL\Connection;

class UserRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        return $row ? $this->hydrateUser($row) : null;
    }

    public function findById(int $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM users WHERE id = ? AND is_active = 1',
            [$id]
        );

        return $row ? $this->hydrateUser($row) : null;
    }

    public function create(User $user): int
    {
        $this->connection->insert('users', [
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'is_active' => $user->isActive() ? 1 : 0,
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(User $user): bool
    {
        if (!$user->getId()) {
            throw new \InvalidArgumentException('User ID is required for update');
        }

        $affectedRows = $this->connection->update('users', [
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'is_active' => $user->isActive() ? 1 : 0,
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], [
            'id' => $user->getId()
        ]);

        return $affectedRows > 0;
    }

    private function hydrateUser(array $row): User
    {
        return new User(
            name: $row['name'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            isActive: (bool) $row['is_active'],
            id: (int) $row['id'],
            createdAt: new \DateTime($row['created_at']),
            updatedAt: new \DateTime($row['updated_at'])
        );
    }
}