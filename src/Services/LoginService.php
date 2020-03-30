<?php

namespace Binaryk\LaravelRestify\Services;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class LoginService
{
    use AuthenticatesUsers;

    public static function make(Request $request)
    {
        return resolve(static::class)->login($request);
    }
}
