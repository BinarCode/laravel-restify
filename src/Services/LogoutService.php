<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Services\Concerns\AuthenticatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LogoutService
{
    use AuthenticatesUsers;

    public static function make(Request $request): Response
    {
        /** * @var User */
        $user = Auth::user();

        if ($user instanceof Authenticatable) {
            return resolve(static::class)->logout($request);
        }

        throw new AuthenticatableUserException(__('User is not authenticated.'));
    }
}
