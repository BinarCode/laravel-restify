<?php

namespace Binaryk\LaravelRestify\Services;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Services;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResetPasswordService
{
    use ResetsPasswords;

    public static function make(Request $request)
    {
        return resolve(static::class)->reset($request);
    }
}
