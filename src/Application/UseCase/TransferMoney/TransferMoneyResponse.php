<?php

declare(strict_types=1);

namespace PicPay\Application\UseCase\TransferMoney;

class TransferMoneyResponse
{
    public function __construct(
        public readonly int $transferId,
        public readonly string $status,
        public readonly string $message
    ) {
    }
}

