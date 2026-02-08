<?php

declare(strict_types=1);

namespace PicPay\Domain\Service;

interface AuthorizationServiceInterface
{
    public function authorize(): bool;
}

