<?php

declare(strict_types=1);

namespace PicPay\Domain\ValueObject;

enum UserType: string
{
    case COMMON = 'common';
    case MERCHANT = 'merchant';

    public function label(): string
    {
        return match ($this) {
            self::COMMON => 'Usuário Comum',
            self::MERCHANT => 'Lojista',
        };
    }
}

