<?php

namespace App;

use RuntimeException;

class MailDeliveryFailure extends RuntimeException
{
    /**
     * Retain only the operation and exception type, never transport contents or a previous exception.
     */
    public function __construct(string $operation, string $exceptionClass)
    {
        parent::__construct($operation.' mail delivery failed ('.$exceptionClass.').');
    }
}
