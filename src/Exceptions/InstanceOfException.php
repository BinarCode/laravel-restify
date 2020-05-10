<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Exception;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class InstanceOfException extends Exception
{
    public static function because($message = '')
    {
        return new static($message);
    }
}
