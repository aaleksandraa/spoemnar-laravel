<?php

namespace App\Exceptions;

use Exception;

class ImageUploadException extends Exception
{
    public function __construct(string $message = 'Image upload failed', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
