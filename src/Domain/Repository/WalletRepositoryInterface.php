<?php

declare(strict_types=1);

namespace PicPay\Domain\Repository;

use PicPay\Domain\Entity\Wallet;

interface WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet;

    public function save(Wallet $wallet): void;

    public function lockForUpdate(int $userId): ?Wallet;
}

