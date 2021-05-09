<?php

namespace Binaryk\LaravelRestify\Exceptions;

use RuntimeException;

class RepositoryNotFoundException extends RuntimeException
{
    public static function make(string $message): self
    {
        return new static($message, 404);
    }
}
