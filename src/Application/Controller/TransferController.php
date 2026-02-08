<?php

declare(strict_types=1);

namespace PicPay\Application\Controller;

use PicPay\Application\UseCase\TransferMoney\TransferMoneyRequest;
use PicPay\Application\UseCase\TransferMoney\TransferMoneyUseCase;
use PicPay\Domain\Exception\InsufficientBalanceException;
use PicPay\Domain\Exception\MerchantCannotSendMoneyException;
use PicPay\Domain\Exception\UnauthorizedTransferException;
use PicPay\Domain\Exception\UserNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransferController
{
    public function __construct(
        private readonly TransferMoneyUseCase $transferMoneyUseCase
    ) {
    }

    public function transfer(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!isset($data['payer']) || !isset($data['payee']) || !isset($data['value'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Missing required fields: payer, payee, value',
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $transferRequest = new TransferMoneyRequest(
                payerId: (int) $data['payer'],
                payeeId: (int) $data['payee'],
                value: (float) $data['value']
            );

            $transferResponse = $this->transferMoneyUseCase->execute($transferRequest);

            $response->getBody()->write(json_encode([
                'transfer_id' => $transferResponse->transferId,
                'status' => $transferResponse->status,
                'message' => $transferResponse->message,
            ]));

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (UserNotFoundException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (MerchantCannotSendMoneyException | InsufficientBalanceException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (UnauthorizedTransferException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));

            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(),
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}

