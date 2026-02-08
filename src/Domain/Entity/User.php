<?php

declare(strict_types=1);

namespace PicPay\Domain\Entity;

use PicPay\Domain\ValueObject\UserType;

class User
{
    private ?int $id = null;

    public function __construct(
        private readonly string $fullName,
        private readonly string $cpf,
        private readonly string $email,
        private readonly string $password,
        private readonly UserType $userType,
        private Wallet $wallet
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function setWallet(Wallet $wallet): void
    {
        $this->wallet = $wallet;
    }

    public function isMerchant(): bool
    {
        return $this->userType === UserType::MERCHANT;
    }

    public function canSendMoney(): bool
    {
        return !$this->isMerchant();
    }
}

