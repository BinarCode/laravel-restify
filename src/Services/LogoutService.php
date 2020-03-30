<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @package Binaryk\LaravelRestify\Services;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class LogoutService
{
    use AuthenticatesUsers;

    public static function make(Request $request)
    {
        /**
         * @var User
         */
        $user = Auth::user();

        if ($user instanceof Authenticatable) {
            return resolve(static::class)->logout($request);
        } else {
            throw new AuthenticatableUserException(__('User is not authenticated.'));
        }
    }
}
