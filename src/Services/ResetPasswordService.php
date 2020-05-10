<?php

namespace Binaryk\LaravelRestify\Services;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResetPasswordService
{
    use ResetsPasswords;

    protected $authService;

    public static function make(Request $request, AuthService $authService)
    {
        return resolve(static::class)
            ->reset($request);
    }


    protected function usingAuthService(AuthService $authService)
    {
        $this->authService = $authService;

        return $this;
    }
}
