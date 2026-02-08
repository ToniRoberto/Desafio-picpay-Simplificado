<?php

declare(strict_types=1);

namespace PicPay\Domain\Exception;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient balance')
    {
        parent::__construct($message);
    }
}

