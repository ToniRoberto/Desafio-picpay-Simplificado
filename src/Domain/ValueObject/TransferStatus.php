<?php

declare(strict_types=1);

namespace PicPay\Domain\ValueObject;

enum TransferStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERTED = 'reverted';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::COMPLETED => 'Concluída',
            self::FAILED => 'Falhou',
            self::REVERTED => 'Revertida',
        };
    }
}

