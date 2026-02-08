<?php

declare(strict_types=1);

namespace PicPay\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PicPay\Application\UseCase\TransferMoney\TransferMoneyRequest;
use PicPay\Application\UseCase\TransferMoney\TransferMoneyUseCase;
use PicPay\Domain\Entity\User;
use PicPay\Domain\Entity\Wallet;
use PicPay\Domain\Repository\TransferRepositoryInterface;
use PicPay\Domain\Repository\UserRepositoryInterface;
use PicPay\Domain\Repository\WalletRepositoryInterface;
use PicPay\Domain\ValueObject\UserType;
use PicPay\Infrastructure\Repository\TransferRepository;
use PicPay\Infrastructure\Repository\UserRepository;
use PicPay\Infrastructure\Repository\WalletRepository;
use PicPay\Infrastructure\Service\AuthorizationService;
use PicPay\Infrastructure\Service\NotificationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransferMoneyUseCaseTest extends TestCase
{
    private \Doctrine\DBAL\Connection $connection;
    private UserRepositoryInterface $userRepository;
    private WalletRepositoryInterface $walletRepository;
    private TransferRepositoryInterface $transferRepository;
    private LoggerInterface $logger;
    private TransferMoneyUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $connectionParams = [
            'dbname' => $_ENV['DB_NAME'] ?? 'picpay_db',
            'user' => $_ENV['DB_USER'] ?? 'picpay_user',
            'password' => $_ENV['DB_PASSWORD'] ?? 'picpay_password',
            'host' => $_ENV['DB_HOST'] ?? 'db',
            'port' => $_ENV['DB_PORT'] ?? 5432,
            'driver' => 'pdo_pgsql',
        ];

        $this->connection = DriverManager::getConnection($connectionParams);

        // Clean database
        $this->connection->executeStatement('TRUNCATE TABLE transfers, wallets, users RESTART IDENTITY CASCADE');

        $this->userRepository = new UserRepository($this->connection);
        $this->walletRepository = new WalletRepository($this->connection);
        $this->transferRepository = new TransferRepository($this->connection);

        $testHandler = new TestHandler();
        $this->logger = new Logger('test', [$testHandler]);

        $httpClient = new Client();
        $authorizationService = new AuthorizationService(
            $httpClient,
            $_ENV['AUTHORIZATION_SERVICE_URL'] ?? 'https://util.devi.tools/api/v2/authorize',
            $this->logger
        );
        $notificationService = new NotificationService(
            $httpClient,
            $_ENV['NOTIFICATION_SERVICE_URL'] ?? 'https://util.devi.tools/api/v1/notify',
            $this->logger
        );

        $this->useCase = new TransferMoneyUseCase(
            $this->userRepository,
            $this->walletRepository,
            $this->transferRepository,
            $authorizationService,
            $notificationService,
            $this->logger
        );
    }

    public function testSuccessfulTransfer(): void
    {
        // Create payer
        $payer = new User(
            fullName: 'John Doe',
            cpf: '12345678901',
            email: 'john@example.com',
            password: 'password123',
            userType: UserType::COMMON,
            wallet: new Wallet(userId: 0, balance: 1000.0)
        );
        $this->userRepository->save($payer);

        // Create payee
        $payee = new User(
            fullName: 'Jane Smith',
            cpf: '98765432100',
            email: 'jane@example.com',
            password: 'password123',
            userType: UserType::COMMON,
            wallet: new Wallet(userId: 0, balance: 500.0)
        );
        $this->userRepository->save($payee);

        // Execute transfer
        $request = new TransferMoneyRequest(
            payerId: $payer->getId() ?? 0,
            payeeId: $payee->getId() ?? 0,
            value: 200.0
        );

        $response = $this->useCase->execute($request);

        $this->assertEquals('completed', $response->status);
        $this->assertNotNull($response->transferId);

        // Verify balances
        $payerWallet = $this->walletRepository->findByUserId($payer->getId() ?? 0);
        $payeeWallet = $this->walletRepository->findByUserId($payee->getId() ?? 0);

        $this->assertEquals(800.0, $payerWallet?->getBalance());
        $this->assertEquals(700.0, $payeeWallet?->getBalance());
    }

    public function testTransferFailsWhenInsufficientBalance(): void
    {
        // Create payer with low balance
        $payer = new User(
            fullName: 'Poor User',
            cpf: '11111111111',
            email: 'poor@example.com',
            password: 'password123',
            userType: UserType::COMMON,
            wallet: new Wallet(userId: 0, balance: 50.0)
        );
        $this->userRepository->save($payer);

        // Create payee
        $payee = new User(
            fullName: 'Rich User',
            cpf: '22222222222',
            email: 'rich@example.com',
            password: 'password123',
            userType: UserType::COMMON,
            wallet: new Wallet(userId: 0, balance: 1000.0)
        );
        $this->userRepository->save($payee);

        // Try to transfer more than balance
        $request = new TransferMoneyRequest(
            payerId: $payer->getId() ?? 0,
            payeeId: $payee->getId() ?? 0,
            value: 200.0
        );

        $this->expectException(\PicPay\Domain\Exception\InsufficientBalanceException::class);
        $this->useCase->execute($request);
    }

    public function testMerchantCannotSendMoney(): void
    {
        // Create merchant payer
        $merchant = new User(
            fullName: 'Merchant Store',
            cpf: '33333333333',
            email: 'merchant@example.com',
            password: 'password123',
            userType: UserType::MERCHANT,
            wallet: new Wallet(userId: 0, balance: 10000.0)
        );
        $this->userRepository->save($merchant);

        // Create payee
        $payee = new User(
            fullName: 'Customer',
            cpf: '44444444444',
            email: 'customer@example.com',
            password: 'password123',
            userType: UserType::COMMON,
            wallet: new Wallet(userId: 0, balance: 100.0)
        );
        $this->userRepository->save($payee);

        // Try to transfer
        $request = new TransferMoneyRequest(
            payerId: $merchant->getId() ?? 0,
            payeeId: $payee->getId() ?? 0,
            value: 100.0
        );

        $this->expectException(\PicPay\Domain\Exception\MerchantCannotSendMoneyException::class);
        $this->useCase->execute($request);
    }
}

