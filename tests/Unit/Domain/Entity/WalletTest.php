<?php

declare(strict_types=1);

namespace PicPay\Tests\Unit\Domain\Entity;

use PicPay\Domain\Entity\Wallet;
use PHPUnit\Framework\TestCase;

class WalletTest extends TestCase
{
    public function testWalletCreation(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);

        $this->assertEquals(1, $wallet->getUserId());
        $this->assertEquals(100.0, $wallet->getBalance());
    }

    public function testWalletCannotHaveNegativeBalance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Wallet(userId: 1, balance: -10.0);
    }

    public function testHasSufficientBalance(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);

        $this->assertTrue($wallet->hasSufficientBalance(50.0));
        $this->assertTrue($wallet->hasSufficientBalance(100.0));
        $this->assertFalse($wallet->hasSufficientBalance(150.0));
    }

    public function testDebit(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);
        $wallet->debit(30.0);

        $this->assertEquals(70.0, $wallet->getBalance());
    }

    public function testDebitThrowsExceptionWhenInsufficientBalance(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient balance');
        $wallet->debit(150.0);
    }

    public function testDebitThrowsExceptionWhenAmountIsZeroOrNegative(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);

        $this->expectException(\InvalidArgumentException::class);
        $wallet->debit(0);

        $this->expectException(\InvalidArgumentException::class);
        $wallet->debit(-10);
    }

    public function testCredit(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);
        $wallet->credit(50.0);

        $this->assertEquals(150.0, $wallet->getBalance());
    }

    public function testCreditThrowsExceptionWhenAmountIsZeroOrNegative(): void
    {
        $wallet = new Wallet(userId: 1, balance: 100.0);

        $this->expectException(\InvalidArgumentException::class);
        $wallet->credit(0);

        $this->expectException(\InvalidArgumentException::class);
        $wallet->credit(-10);
    }
}

