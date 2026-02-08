<?php

declare(strict_types=1);

namespace PicPay\Domain\Repository;

use PicPay\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByCpf(string $cpf): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}

