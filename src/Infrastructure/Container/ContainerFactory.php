<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Container;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PicPay\Domain\Repository\TransferRepositoryInterface;
use PicPay\Domain\Repository\UserRepositoryInterface;
use PicPay\Domain\Repository\WalletRepositoryInterface;
use PicPay\Domain\Service\AuthorizationServiceInterface;
use PicPay\Domain\Service\NotificationServiceInterface;
use PicPay\Infrastructure\Repository\TransferRepository;
use PicPay\Infrastructure\Repository\UserRepository;
use PicPay\Infrastructure\Repository\WalletRepository;
use PicPay\Infrastructure\Service\AuthorizationService;
use PicPay\Infrastructure\Service\MockAuthorizationService;
use PicPay\Infrastructure\Service\NotificationService;
use Psr\Log\LoggerInterface;

class ContainerFactory
{
    public static function create(): Container
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
            $dotenv->load();
        }

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAnnotations(false);

        $builder->addDefinitions([
            // Database
            Connection::class => function () {
                $connectionParams = [
                    'dbname' => $_ENV['DB_NAME'] ?? 'picpay_db',
                    'user' => $_ENV['DB_USER'] ?? 'picpay_user',
                    'password' => $_ENV['DB_PASSWORD'] ?? 'picpay_password',
                    'host' => $_ENV['DB_HOST'] ?? 'db',
                    'port' => $_ENV['DB_PORT'] ?? 5432,
                    'driver' => 'pdo_pgsql',
                ];

                return DriverManager::getConnection($connectionParams);
            },

            // Logger
            LoggerInterface::class => function () {
                $logger = new Logger('picpay');
                $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
                return $logger;
            },

            // HTTP Client
            Client::class => function () {
                return new Client([
                    'timeout' => 5,
                    'connect_timeout' => 5,
                ]);
            },

            // Repositories
            UserRepositoryInterface::class => \DI\autowire(UserRepository::class),
            WalletRepositoryInterface::class => \DI\autowire(WalletRepository::class),
            TransferRepositoryInterface::class => \DI\autowire(TransferRepository::class),

            // Services
            AuthorizationServiceInterface::class => function (Container $container) {
                // Use mock se a variável de ambiente estiver definida
                if (($_ENV['USE_MOCK_AUTHORIZATION'] ?? 'false') === 'true') {
                    return $container->get(MockAuthorizationService::class);
                }
                
                // Caso contrário, usa o serviço real
                $httpClient = $container->get(Client::class);
                $logger = $container->get(LoggerInterface::class);
                $authorizationUrl = $_ENV['AUTHORIZATION_SERVICE_URL'] ?? 'https://util.devi.tools/api/v2/authorize';
                
                return new AuthorizationService($httpClient, $authorizationUrl, $logger);
            },
            NotificationServiceInterface::class => \DI\autowire(NotificationService::class)
                ->constructorParameter('notificationUrl', $_ENV['NOTIFICATION_SERVICE_URL'] ?? 'https://util.devi.tools/api/v1/notify'),
        ]);

        return $builder->build();
    }
}

