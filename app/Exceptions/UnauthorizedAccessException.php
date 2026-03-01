<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedAccessException extends Exception
{
    public function __construct(string $message = 'Unauthorized access to this resource', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
