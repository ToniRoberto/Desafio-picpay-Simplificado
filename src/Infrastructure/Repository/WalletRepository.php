<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Repository;

use Doctrine\DBAL\Connection;
use PicPay\Domain\Entity\Wallet;
use PicPay\Domain\Repository\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function findByUserId(int $userId): ?Wallet
    {
        $sql = 'SELECT * FROM wallets WHERE user_id = :user_id';
        $row = $this->connection->fetchAssociative($sql, ['user_id' => $userId]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function lockForUpdate(int $userId): ?Wallet
    {
        $sql = 'SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE';
        $row = $this->connection->fetchAssociative($sql, ['user_id' => $userId]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(Wallet $wallet): void
    {
        $data = [
            'user_id' => $wallet->getUserId(),
            'balance' => $wallet->getBalance(),
        ];

        if ($wallet->getId() === null) {
            $this->connection->insert('wallets', $data);
            $wallet->setId((int) $this->connection->lastInsertId());
        } else {
            $this->connection->update('wallets', $data, ['id' => $wallet->getId()]);
        }
    }

    private function hydrate(array $row): Wallet
    {
        $wallet = new Wallet(
            userId: (int) $row['user_id'],
            balance: (float) $row['balance']
        );

        $wallet->setId((int) $row['id']);

        return $wallet;
    }
}

