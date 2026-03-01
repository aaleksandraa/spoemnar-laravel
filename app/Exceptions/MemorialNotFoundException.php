<?php

namespace App\Exceptions;

use Exception;

class MemorialNotFoundException extends Exception
{
    public function __construct(string $message = 'Memorial not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
