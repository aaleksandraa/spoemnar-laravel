<?php

namespace App\Exceptions;

use Exception;

class InvalidYouTubeUrlException extends Exception
{
    public function __construct(string $message = 'Invalid YouTube URL format', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
