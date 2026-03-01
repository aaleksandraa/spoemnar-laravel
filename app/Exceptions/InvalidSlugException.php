<?php

namespace App\Exceptions;

use Exception;

class InvalidSlugException extends Exception
{
    public function __construct(string $message = 'Invalid slug format', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
