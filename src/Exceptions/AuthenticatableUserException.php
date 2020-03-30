<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Exception;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthenticatableUserException extends Exception
{
    public static function wrongInstance(): self
    {
        $message = __("Repository model should be an instance of \Illuminate\Contracts\Auth\Authenticatable");

        return new static($message);
    }
}
