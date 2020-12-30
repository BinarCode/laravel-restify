<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends RestController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        return $this->authService->login($request);
    }

    public function register(Request $request)
    {
        return $this->authService->register($request);
    }

    public function verify(Request $request, $id, $hash = null)
    {
        return $this->authService->verify($request, $id, $hash);
    }

    public function forgotPassword(Request $request)
    {
        $this->authService->forgotPassword($request);

        return response();
    }

    public function resetPassword(Request $request)
    {
        $this->authService->resetPassword($request);

        return response();
    }
}
