<?php

declare(strict_types=1);

namespace PicPay\Application\UseCase\TransferMoney;

use PicPay\Domain\Entity\Transfer;
use PicPay\Domain\Entity\Wallet;
use PicPay\Domain\Exception\InsufficientBalanceException;
use PicPay\Domain\Exception\MerchantCannotSendMoneyException;
use PicPay\Domain\Exception\UnauthorizedTransferException;
use PicPay\Domain\Exception\UserNotFoundException;
use PicPay\Domain\Repository\TransferRepositoryInterface;
use PicPay\Domain\Repository\UserRepositoryInterface;
use PicPay\Domain\Repository\WalletRepositoryInterface;
use PicPay\Domain\Service\AuthorizationServiceInterface;
use PicPay\Domain\Service\NotificationServiceInterface;
use PicPay\Domain\ValueObject\TransferStatus;
use Psr\Log\LoggerInterface;

class TransferMoneyUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly AuthorizationServiceInterface $authorizationService,
        private readonly NotificationServiceInterface $notificationService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(TransferMoneyRequest $request): TransferMoneyResponse
    {
        $this->logger->info('Starting transfer', [
            'payer_id' => $request->payerId,
            'payee_id' => $request->payeeId,
            'value' => $request->value,
        ]);

        // Buscar usuários
        $payer = $this->userRepository->findById($request->payerId);
        $payee = $this->userRepository->findById($request->payeeId);

        if ($payer === null) {
            throw new UserNotFoundException("Payer with ID {$request->payerId} not found");
        }

        if ($payee === null) {
            throw new UserNotFoundException("Payee with ID {$request->payeeId} not found");
        }

        // Validar se lojista pode enviar dinheiro
        if (!$payer->canSendMoney()) {
            throw new MerchantCannotSendMoneyException(
                "User {$request->payerId} is a merchant and cannot send money"
            );
        }

        // Criar transferência pendente
        $transfer = new Transfer(
            payerId: $request->payerId,
            payeeId: $request->payeeId,
            value: $request->value,
            status: TransferStatus::PENDING
        );
        $transfer->setCreatedAt(new \DateTimeImmutable());

        $this->transferRepository->save($transfer);

        $payerWallet = null;
        $payeeWallet = null;
        $debitExecuted = false;

        try {
            // Lock da carteira do pagador para evitar race conditions
            $payerWallet = $this->walletRepository->lockForUpdate($request->payerId);
            if ($payerWallet === null) {
                throw new UserNotFoundException("Wallet for payer {$request->payerId} not found");
            }

            // Validar saldo
            if (!$payerWallet->hasSufficientBalance($request->value)) {
                $transfer->setStatus(TransferStatus::FAILED);
                $transfer->setUpdatedAt(new \DateTimeImmutable());
                $this->transferRepository->save($transfer);

                throw new InsufficientBalanceException(
                    "Payer {$request->payerId} has insufficient balance"
                );
            }

            // Autorizar transferência
            if (!$this->authorizationService->authorize()) {
                $transfer->setStatus(TransferStatus::FAILED);
                $transfer->setUpdatedAt(new \DateTimeImmutable());
                $this->transferRepository->save($transfer);

                throw new UnauthorizedTransferException('Transfer not authorized by external service');
            }

            // Buscar carteira do recebedor
            $payeeWallet = $this->walletRepository->findByUserId($request->payeeId);
            if ($payeeWallet === null) {
                throw new UserNotFoundException("Wallet for payee {$request->payeeId} not found");
            }

            // Executar transferência (debitar e creditar)
            $payerWallet->debit($request->value);
            $debitExecuted = true;
            $payeeWallet->credit($request->value);

            // Salvar carteiras atualizadas
            $this->walletRepository->save($payerWallet);
            $this->walletRepository->save($payeeWallet);

            // Marcar transferência como concluída
            $transfer->setStatus(TransferStatus::COMPLETED);
            $transfer->setUpdatedAt(new \DateTimeImmutable());
            $this->transferRepository->save($transfer);

            // Enviar notificação (não bloqueia a operação se falhar)
            try {
                $this->notificationService->notify(
                    $request->payeeId,
                    "You received a transfer of {$request->value} from {$payer->getFullName()}"
                );
            } catch (\Exception $e) {
                $this->logger->warning('Failed to send notification', [
                    'payee_id' => $request->payeeId,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->logger->info('Transfer completed successfully', [
                'transfer_id' => $transfer->getId(),
                'payer_id' => $request->payerId,
                'payee_id' => $request->payeeId,
                'value' => $request->value,
            ]);

            return new TransferMoneyResponse(
                transferId: $transfer->getId() ?? 0,
                status: $transfer->getStatus()->value,
                message: 'Transfer completed successfully'
            );
        } catch (\Exception $e) {
            // Reverter transferência em caso de erro
            $this->logger->error('Transfer failed, reverting', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
            ]);

            // Se já debitou, reverter o débito
            if ($debitExecuted && $payerWallet !== null) {
                try {
                    $payerWallet->credit($request->value);
                    $this->walletRepository->save($payerWallet);
                    $this->logger->info('Transfer reverted successfully', [
                        'transfer_id' => $transfer->getId(),
                    ]);
                } catch (\Exception $revertException) {
                    $this->logger->critical('Failed to revert transfer', [
                        'transfer_id' => $transfer->getId(),
                        'error' => $revertException->getMessage(),
                    ]);
                }
            }

            $transfer->setStatus(TransferStatus::FAILED);
            $transfer->setUpdatedAt(new \DateTimeImmutable());
            $this->transferRepository->save($transfer);

            throw $e;
        }
    }
}

