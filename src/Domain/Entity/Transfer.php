<?php

declare(strict_types=1);

namespace PicPay\Domain\Entity;

use DateTimeImmutable;
use PicPay\Domain\ValueObject\TransferStatus;

class Transfer
{
    private ?int $id = null;
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        private readonly int $payerId,
        private readonly int $payeeId,
        private readonly float $value,
        private TransferStatus $status = TransferStatus::PENDING
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Transfer value must be greater than zero');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPayerId(): int
    {
        return $this->payerId;
    }

    public function getPayeeId(): int
    {
        return $this->payeeId;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getStatus(): TransferStatus
    {
        return $this->status;
    }

    public function setStatus(TransferStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransferStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransferStatus::FAILED;
    }
}

