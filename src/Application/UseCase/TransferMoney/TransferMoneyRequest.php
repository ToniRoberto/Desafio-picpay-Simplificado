<?php

declare(strict_types=1);

namespace PicPay\Application\UseCase\TransferMoney;

class TransferMoneyRequest
{
    public function __construct(
        public readonly int $payerId,
        public readonly int $payeeId,
        public readonly float $value
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Transfer value must be greater than zero');
        }

        if ($payerId === $payeeId) {
            throw new \InvalidArgumentException('Payer and payee cannot be the same');
        }
    }
}

