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

echo "🔄 Running database migrations...\n";

try {
    // Create migrations table if not exists
    $connection->executeStatement('
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // Get list of executed migrations
    $executedMigrations = $connection->fetchFirstColumn('SELECT migration FROM migrations');

    // Run pending migrations
    $migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql');
    sort($migrationFiles);

    foreach ($migrationFiles as $file) {
        $migrationName = basename($file, '.sql');
        
        if (in_array($migrationName, $executedMigrations)) {
            echo "⏭️  Skipping {$migrationName} (already executed)\n";
            continue;
        }

        echo "▶️  Running {$migrationName}...\n";
        
        $sql = file_get_contents($file);
        $connection->executeStatement($sql);
        
        // Record migration as executed
        $connection->insert('migrations', [
            'migration' => $migrationName
        ]);
        
        echo "✅ {$migrationName} completed\n";
    }

    echo "\n🎉 All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}