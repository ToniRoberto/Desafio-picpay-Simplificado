<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use PicPay\Domain\Entity\User;
use PicPay\Domain\Entity\Wallet;
use PicPay\Domain\Repository\UserRepositoryInterface;
use PicPay\Domain\Repository\WalletRepositoryInterface;
use PicPay\Domain\ValueObject\UserType;
use PicPay\Infrastructure\Repository\UserRepository;
use PicPay\Infrastructure\Repository\WalletRepository;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

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

    $userRepository = new UserRepository($connection);
    $walletRepository = new WalletRepository($connection);

    echo "Seeding users...\n";

    // Create common users
    $user1 = new User(
        fullName: 'João Silva',
        cpf: '12345678901',
        email: 'joao@example.com',
        password: password_hash('password123', PASSWORD_DEFAULT),
        userType: UserType::COMMON,
        wallet: new Wallet(userId: 0, balance: 1000.0)
    );
    $userRepository->save($user1);
    echo "Created user: João Silva (ID: {$user1->getId()})\n";

    $user2 = new User(
        fullName: 'Maria Santos',
        cpf: '98765432100',
        email: 'maria@example.com',
        password: password_hash('password123', PASSWORD_DEFAULT),
        userType: UserType::COMMON,
        wallet: new Wallet(userId: 0, balance: 500.0)
    );
    $userRepository->save($user2);
    echo "Created user: Maria Santos (ID: {$user2->getId()})\n";

    // Create merchant
    $merchant = new User(
        fullName: 'Loja do Zé',
        cpf: '11111111111',
        email: 'loja@example.com',
        password: password_hash('password123', PASSWORD_DEFAULT),
        userType: UserType::MERCHANT,
        wallet: new Wallet(userId: 0, balance: 0.0)
    );
    $userRepository->save($merchant);
    echo "Created merchant: Loja do Zé (ID: {$merchant->getId()})\n";

    echo "\nSeed completed successfully!\n";
    echo "\nUsers created:\n";
    echo "- User 1 (Common): ID {$user1->getId()}, Balance: 1000.0\n";
    echo "- User 2 (Common): ID {$user2->getId()}, Balance: 500.0\n";
    echo "- Merchant: ID {$merchant->getId()}, Balance: 0.0\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

