<?php

declare(strict_types=1);

namespace PicPay\Domain\Service;

interface NotificationServiceInterface
{
    public function notify(int $userId, string $message): bool;
}

