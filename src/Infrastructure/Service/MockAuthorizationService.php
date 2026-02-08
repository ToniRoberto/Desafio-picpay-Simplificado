<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Service;

use PicPay\Domain\Service\AuthorizationServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock do serviço de autorização para testes
 * Sempre retorna autorizado para permitir testes locais
 */
class MockAuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function authorize(): bool
    {
        $this->logger->info('Mock authorization service: Always authorizing', [
            'authorized' => true,
        ]);

        return true;
    }
}

