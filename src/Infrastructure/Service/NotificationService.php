<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PicPay\Domain\Service\NotificationServiceInterface;
use Psr\Log\LoggerInterface;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly Client $httpClient,
        private readonly string $notificationUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    public function notify(int $userId, string $message): bool
    {
        try {
            $response = $this->httpClient->post($this->notificationUrl, [
                'json' => [
                    'user_id' => $userId,
                    'message' => $message,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $success = isset($body['message']) && strtolower($body['message']) === 'success';

            $this->logger->info('Notification service response', [
                'user_id' => $userId,
                'success' => $success,
                'response' => $body,
            ]);

            return $success;
        } catch (GuzzleException $e) {
            // Não lança exceção pois o serviço pode estar indisponível
            // A transferência já foi concluída, então apenas logamos o erro
            $this->logger->warning('Notification service error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

