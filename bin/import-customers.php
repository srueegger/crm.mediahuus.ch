<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Repositories\InsuranceAssessmentRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\PurchaseRepository;
use App\Services\CustomerFieldMap;
use App\Services\CustomerResolver;
use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Dotenv\Dotenv;

$dryRun = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
        continue;
    }
    fwrite(STDERR, "Unbekanntes Argument: {$arg}\n");
    fwrite(STDERR, "Verwendung: php bin/import-customers.php [--dry-run]\n");
    exit(2);
}

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->load();
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

/** @var Connection $connection */
$connection = $container->get(Connection::class);
$documents = $container->get(DocumentRepository::class);
$purchases = $container->get(PurchaseRepository::class);
$assessments = $container->get(InsuranceAssessmentRepository::class);
$invoices = $container->get(InvoiceRepository::class);
$resolver = $container->get(CustomerResolver::class);

$countCustomers = static fn (): int => (int) $connection->fetchOne('SELECT COUNT(*) FROM customers');

$connection->beginTransaction();

try {
    $before = $countCustomers();
    $processed = 0;
    $linked = 0;
    $skipped = 0;

    foreach ($documents->findWithoutCustomer() as $doc) {
        $processed++;
        $id = (int) $doc->getId();
        $type = $doc->getDocType();

        $data = CustomerFieldMap::dataFromDocument(
            $doc,
            $type === Document::TYPE_PURCHASE ? $purchases->findByDocumentId($id) : null,
            $type === Document::TYPE_INSURANCE ? $assessments->findByDocumentId($id) : null,
            $type === Document::TYPE_INVOICE ? $invoices->findByDocumentId($id) : null,
        );

        $customerId = $resolver->resolve($data, null);
        if ($customerId === null) {
            $skipped++;
            continue;
        }

        $documents->setCustomerId($id, $customerId);
        $linked++;
    }

    $created = $countCustomers() - $before;

    if ($dryRun) {
        $connection->rollBack();
    } else {
        $connection->commit();
    }
} catch (Throwable $e) {
    if ($connection->isTransactionActive()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'Import fehlgeschlagen: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Dokumente verarbeitet: {$processed}, verknüpft: {$linked}, ohne Kunde: {$skipped}, neue Kunden: {$created}"
    . ($dryRun ? ' (Testlauf, nichts gespeichert)' : '') . "\n";
