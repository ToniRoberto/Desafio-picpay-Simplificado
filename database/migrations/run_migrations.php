<?php

declare(strict_types=1);

// Get the project root directory - try multiple approaches
$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

// If not found, try absolute path (for Docker containers)
if (!file_exists($autoloadPath)) {
    $autoloadPath = '/var/www/html/vendor/autoload.php';
    $projectRoot = '/var/www/html';
}

if (!file_exists($autoloadPath)) {
    die("Error: vendor/autoload.php not found at {$autoloadPath}. Please run 'composer install' first.\n");
}

require_once $autoloadPath;

use Doctrine\DBAL\DriverManager;

// Load .env file if it exists
if (file_exists($projectRoot . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
    $dotenv->load();
}

$connectionParams = [
    'dbname' => $_ENV['DB_NAME'] ?? 'picpay_db',
    'user' => $_ENV['DB_USER'] ?? 'picpay_user',
    'password' => $_ENV['DB_PASSWORD'] ?? 'picpay_password',
    'host' => $_ENV['DB_HOST'] ?? 'db',
    'port' => $_ENV['DB_PORT'] ?? 5432,
    'driver' => 'pdo_pgsql',
];

try {
    $connection = DriverManager::getConnection($connectionParams);
    $connection->connect();

    echo "Connected to database successfully!\n";

    $migrations = [
        '001_create_users_table.sql',
        '002_create_wallets_table.sql',
        '003_create_transfers_table.sql',
    ];

    foreach ($migrations as $migration) {
        $sql = file_get_contents(__DIR__ . '/' . $migration);
        echo "Running migration: {$migration}\n";
        $connection->executeStatement($sql);
        echo "Migration {$migration} completed successfully!\n";
    }

    echo "\nAll migrations completed successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

