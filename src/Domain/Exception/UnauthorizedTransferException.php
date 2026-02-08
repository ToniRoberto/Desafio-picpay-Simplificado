<?php

declare(strict_types=1);

namespace PicPay\Domain\Exception;

use Exception;

class UnauthorizedTransferException extends Exception
{
    public function __construct(string $message = 'Transfer not authorized')
    {
        parent::__construct($message);
    }
}

