<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Exception;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class SanctumUserException extends Exception
{
    public static function wrongConfiguration()
    {
        return new static('Auth provider should be [sanctum] in the configuration [restify.auth.provider].');
    }
}
