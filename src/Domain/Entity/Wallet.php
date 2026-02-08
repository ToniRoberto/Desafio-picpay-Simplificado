<?php

declare(strict_types=1);

namespace PicPay\Domain\Entity;

class Wallet
{
    private ?int $id = null;

    public function __construct(
        private int $userId,
        private float $balance = 0.0
    ) {
        if ($balance < 0) {
            throw new \InvalidArgumentException('Balance cannot be negative');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function debit(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if (!$this->hasSufficientBalance($amount)) {
            throw new \DomainException('Insufficient balance');
        }

        $this->balance -= $amount;
    }

    public function credit(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $this->balance += $amount;
    }
}

