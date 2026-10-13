<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Services\PhoneNormalizer;
use Doctrine\DBAL\Connection;

class CustomerRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function create(Customer $customer): int
    {
        $this->connection->insert('customers', [
            'company_name' => $customer->getCompanyName(),
            'name' => $customer->getName(),
            'phone' => $customer->getPhone(),
            'phone_normalized' => $customer->getPhoneNormalized(),
            'email' => $customer->getEmail(),
            'street' => $customer->getStreet(),
            'zip' => $customer->getZip(),
            'city' => $customer->getCity(),
            'notes' => $customer->getNotes(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(Customer $customer): void
    {
        if ($customer->getId() === null) {
            throw new \InvalidArgumentException('Customer ID is required for update');
        }

        $this->connection->update('customers', [
            'company_name' => $customer->getCompanyName(),
            'name' => $customer->getName(),
            'phone' => $customer->getPhone(),
            'phone_normalized' => $customer->getPhoneNormalized(),
            'email' => $customer->getEmail(),
            'street' => $customer->getStreet(),
            'zip' => $customer->getZip(),
            'city' => $customer->getCity(),
            'notes' => $customer->getNotes(),
        ], ['id' => $customer->getId()]);
    }

    public function findById(int $id): ?Customer
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM customers WHERE id = ?', [$id]);

        return $row ? $this->hydrate($row) : null;
    }

    public function delete(int $id): void
    {
        $this->connection->delete('customers', ['id' => $id]);
    }

    /**
     * @return Customer[]
     */
    public function search(string $q, int $limit = 10): array
    {
        [$where, $params] = $this->buildSearch($q);
        $sql = 'SELECT * FROM customers' . ($where !== '' ? ' WHERE ' . $where : '')
            . ' ORDER BY name ASC, id ASC LIMIT ' . max(1, $limit);

        return array_map([$this, 'hydrate'], $this->connection->fetchAllAssociative($sql, $params));
    }

    /**
     * @return array{items: list<array{customer: Customer, document_count: int, last_document_at: ?string}>, total: int}
     */
    public function paginate(?string $q, int $page, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        [$where, $params] = $this->buildSearch($q ?? '', 'c.');
        $whereSql = $where !== '' ? ' WHERE ' . $where : '';

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM customers c' . $whereSql,
            $params
        );

        $rows = $this->connection->fetchAllAssociative(
            'SELECT c.*, COUNT(d.id) AS document_count, MAX(d.created_at) AS last_document_at '
            . 'FROM customers c LEFT JOIN documents d ON d.customer_id = c.id'
            . $whereSql
            . ' GROUP BY c.id ORDER BY c.name ASC, c.id ASC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'customer' => $this->hydrate($row),
                'document_count' => (int) $row['document_count'],
                'last_document_at' => $row['last_document_at'] ?? null,
            ];
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @param string|null $email bereits normalisiert (trim + lowercase)
     * @return Customer[]
     */
    public function findCandidates(?string $phoneNormalized, ?string $email, ?string $zip): array
    {
        $conditions = [];
        $params = [];

        if ($phoneNormalized !== null && $phoneNormalized !== '') {
            $conditions[] = 'phone_normalized = ?';
            $params[] = $phoneNormalized;
        }
        if ($email !== null && $email !== '') {
            $conditions[] = 'LOWER(email) = ?';
            $params[] = $email;
        }
        $zip = $zip !== null ? trim($zip) : null;
        if ($zip !== null && $zip !== '') {
            $conditions[] = 'zip = ?';
            $params[] = $zip;
        }

        if ($conditions === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM customers WHERE ' . implode(' OR ', $conditions) . ' ORDER BY id ASC',
            $params
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function buildSearch(string $q, string $alias = ''): array
    {
        $q = trim($q);
        if ($q === '') {
            return ['', []];
        }

        $like = '%' . addcslashes($q, '\\%_') . '%';
        $conditions = [$alias . 'name LIKE ?', $alias . 'company_name LIKE ?', $alias . 'email LIKE ?'];
        $params = [$like, $like, $like];

        if (preg_match_all('/\d/', $q) >= 3) {
            $phone = PhoneNormalizer::normalize($q);
            if ($phone !== null) {
                $conditions[] = $alias . 'phone_normalized LIKE ?';
                $params[] = '%' . $phone . '%';
            }
        }

        return ['(' . implode(' OR ', $conditions) . ')', $params];
    }

    private function hydrate(array $row): Customer
    {
        return new Customer(
            (int) $row['id'],
            (string) $row['name'],
            $row['company_name'],
            $row['phone'],
            $row['email'],
            $row['street'],
            $row['zip'],
            $row['city'],
            $row['notes'],
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}
