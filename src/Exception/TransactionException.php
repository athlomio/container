<?php
declare(strict_types=1);

namespace Archer\Container\Exception;

use Exception;

class TransactionException extends Exception 
{
    public static function noActiveTransaction(): self 
    {
        return new self("No active transaction. You must call `\$transaction->start()` before retrieving, registering or ending transaction-scoped services.");
    }

    public static function transactionAlreadyActive(): self
    {
        return new self("A transaction is already active. You must call `\$transaction->end()` before starting a new one.");
    }

    public static function serviceUnavailable(string $id): self
    {
        return new self("Service '{$id}' could not be resolved. Make sure it is registered.");
    }
}