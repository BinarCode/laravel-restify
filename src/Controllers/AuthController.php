<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
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
        return $this->authService->forgotPassword($request);
    }

    public function resetPassword(Request $request)
    {
        return $this->authService->resetPassword($request);
    }
}
