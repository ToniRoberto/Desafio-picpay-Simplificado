<?php

declare(strict_types=1);

namespace PicPay\Domain\Exception;

use Exception;

class MerchantCannotSendMoneyException extends Exception
{
    public function __construct(string $message = 'Merchants cannot send money')
    {
        parent::__construct($message);
    }
}

