<?php

namespace App\Exceptions;

use Exception;

class BatchLockedException extends Exception
{
    public function __construct(string $message = 'This batch has already been locked or invoiced and cannot be modified.')
    {
        parent::__construct($message);
    }
}
