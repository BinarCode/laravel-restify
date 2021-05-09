<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class UnauthorizedException extends AuthorizationException
{
    public static function make(string $message): self
    {
        return new static($message, 403);
    }
}
