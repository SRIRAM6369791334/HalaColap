<?php

namespace App\Exceptions;

use Exception;

class BoxConservationException extends Exception
{
    public function __construct(string $message = 'Box count conservation violated during entry split.')
    {
        parent::__construct($message);
    }
}
