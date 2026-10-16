<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by WalletService::debit() when the locked, current wallet value
 * cannot cover the amount. Callers catch it to show their own message.
 */
class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly string $wallet,
        public readonly float $available,
        public readonly float $required,
    ) {
        parent::__construct("Insufficient {$wallet}: needs {$required}, has {$available}.");
    }
}
