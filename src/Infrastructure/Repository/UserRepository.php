<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Repository;

use Doctrine\DBAL\Connection;
use PicPay\Domain\Entity\User;
use PicPay\Domain\Entity\Wallet;
use PicPay\Domain\Repository\UserRepositoryInterface;
use PicPay\Domain\ValueObject\UserType;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function findById(int $id): ?User
    {
        $sql = 'SELECT u.*, w.id as wallet_id, w.balance 
                FROM users u 
                LEFT JOIN wallets w ON u.id = w.user_id 
                WHERE u.id = :id';

        $row = $this->connection->fetchAssociative($sql, ['id' => $id]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByCpf(string $cpf): ?User
    {
        $sql = 'SELECT u.*, w.id as wallet_id, w.balance 
                FROM users u 
                LEFT JOIN wallets w ON u.id = w.user_id 
                WHERE u.cpf = :cpf';

        $row = $this->connection->fetchAssociative($sql, ['cpf' => $cpf]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT u.*, w.id as wallet_id, w.balance 
                FROM users u 
                LEFT JOIN wallets w ON u.id = w.user_id 
                WHERE u.email = :email';

        $row = $this->connection->fetchAssociative($sql, ['email' => $email]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(User $user): void
    {
        $data = [
            'full_name' => $user->getFullName(),
            'cpf' => $user->getCpf(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'user_type' => $user->getUserType()->value,
        ];

        if ($user->getId() === null) {
            $this->connection->insert('users', $data);
            $userId = (int) $this->connection->lastInsertId();
            $user->setId($userId);

            // Criar carteira inicial com o saldo do objeto Wallet
            $wallet = $user->getWallet();
            $wallet->setUserId($userId);
            
            $this->connection->insert('wallets', [
                'user_id' => $userId,
                'balance' => $wallet->getBalance(),
            ]);

            $walletId = (int) $this->connection->lastInsertId();
            $wallet->setId($walletId);
        } else {
            $this->connection->update('users', $data, ['id' => $user->getId()]);
        }
    }

    private function hydrate(array $row): User
    {
        $userType = UserType::from($row['user_type']);

        $wallet = new Wallet(
            userId: (int) $row['id'],
            balance: (float) ($row['balance'] ?? 0.0)
        );

        if (isset($row['wallet_id'])) {
            $wallet->setId((int) $row['wallet_id']);
        }

        $user = new User(
            fullName: $row['full_name'],
            cpf: $row['cpf'],
            email: $row['email'],
            password: $row['password'],
            userType: $userType,
            wallet: $wallet
        );

        $user->setId((int) $row['id']);

        return $user;
    }
}

