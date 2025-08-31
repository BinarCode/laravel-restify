<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()->delete();
        }

        Auth::logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}