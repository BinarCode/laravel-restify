<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Services\Concerns\ResetsPasswords;
use Illuminate\Http\Request;

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
