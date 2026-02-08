<?php

declare(strict_types=1);

namespace PicPay\Infrastructure\Repository;

use Doctrine\DBAL\Connection;
use PicPay\Domain\Entity\Transfer;
use PicPay\Domain\Repository\TransferRepositoryInterface;
use PicPay\Domain\ValueObject\TransferStatus;

class TransferRepository implements TransferRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function save(Transfer $transfer): void
    {
        $data = [
            'payer_id' => $transfer->getPayerId(),
            'payee_id' => $transfer->getPayeeId(),
            'value' => $transfer->getValue(),
            'status' => $transfer->getStatus()->value,
            'created_at' => $transfer->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $transfer->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($transfer->getId() === null) {
            $this->connection->insert('transfers', $data);
            $transfer->setId((int) $this->connection->lastInsertId());
        } else {
            $this->connection->update('transfers', $data, ['id' => $transfer->getId()]);
        }
    }

    public function findById(int $id): ?Transfer
    {
        $sql = 'SELECT * FROM transfers WHERE id = :id';
        $row = $this->connection->fetchAssociative($sql, ['id' => $id]);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    private function hydrate(array $row): Transfer
    {
        $transfer = new Transfer(
            payerId: (int) $row['payer_id'],
            payeeId: (int) $row['payee_id'],
            value: (float) $row['value'],
            status: TransferStatus::from($row['status'])
        );

        $transfer->setId((int) $row['id']);

        if ($row['created_at'] !== null) {
            $transfer->setCreatedAt(new \DateTimeImmutable($row['created_at']));
        }

        if ($row['updated_at'] !== null) {
            $transfer->setUpdatedAt(new \DateTimeImmutable($row['updated_at']));
        }

        return $transfer;
    }
}

