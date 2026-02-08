<?php

declare(strict_types=1);

namespace PicPay\Domain\Repository;

use PicPay\Domain\Entity\Transfer;

interface TransferRepositoryInterface
{
    public function save(Transfer $transfer): void;

    public function findById(int $id): ?Transfer;
}

