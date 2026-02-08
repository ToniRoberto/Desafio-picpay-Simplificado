<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PicPay\Domain\Service\AuthorizationServiceInterface;
use Psr\Log\LoggerInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly Client $httpClient,
        private readonly string $authorizationUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    public function authorize(): bool
    {
        try {
            $response = $this->httpClient->get($this->authorizationUrl, [
                'http_errors' => false, // Não lançar exceção em códigos 4xx/5xx
            ]);
            
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            // Considera autorizado se:
            // 1. Status 200 E (message = "authorized" OU data.authorization = true)
            $authorized = $statusCode === 200 && (
                (isset($body['message']) && strtolower($body['message']) === 'authorized') ||
                (isset($body['data']['authorization']) && $body['data']['authorization'] === true)
            );

            $this->logger->info('Authorization service response', [
                'status_code' => $statusCode,
                'authorized' => $authorized,
                'response' => $body,
            ]);

            return $authorized;
        } catch (GuzzleException $e) {
            $this->logger->error('Authorization service error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

