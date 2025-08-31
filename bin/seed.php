<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\Connection;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Create container and get DB connection
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();
$connection = $container->get(Connection::class);

echo "🌱 Seeding database...\n";

try {
    // Seed branches
    echo "▶️  Seeding branches...\n";
    
    // Check if branches already exist
    $branchCount = $connection->fetchOne('SELECT COUNT(*) FROM branches');
    
    if ($branchCount == 0) {
        $connection->insert('branches', [
            'name' => 'Mediahuus Clara',
            'street' => 'Hauptstrasse 123',
            'zip' => '4058',
            'city' => 'Basel',
            'phone' => '+41 61 123 45 67',
            'email' => 'clara@mediahuus.ch',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $connection->insert('branches', [
            'name' => 'Mediahuus Reinach',
            'street' => 'Baselstrasse 456',
            'zip' => '4153',
            'city' => 'Reinach BL',
            'phone' => '+41 61 987 65 43',
            'email' => 'reinach@mediahuus.ch',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "✅ Branches seeded\n";
    } else {
        echo "⏭️  Branches already exist, skipping\n";
    }

    // Seed admin user
    echo "▶️  Seeding admin user...\n";
    
    // Check if admin user already exists
    $adminExists = $connection->fetchOne('SELECT COUNT(*) FROM users WHERE email = ?', ['admin@mediahuus.ch']);
    
    if ($adminExists == 0) {
        $connection->insert('users', [
            'name' => 'Admin User',
            'email' => 'admin@mediahuus.ch',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "✅ Admin user seeded (admin@mediahuus.ch / admin123)\n";
    } else {
        echo "⏭️  Admin user already exists, skipping\n";
    }

    echo "\n🎉 Database seeding completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}