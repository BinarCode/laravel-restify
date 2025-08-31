<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** * @var User $user */
        if (! $user = config('restify.auth.user_model')::query()
            ->whereEmail($request->input('email'))
            ->first()) {
            abort(401, 'Invalid credentials.');
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            abort(401, 'Invalid credentials.');
        }

        Auth::login($user);

        $tokenTtl = config('restify.auth.token_ttl');
        $expiresAt = $tokenTtl ? now()->addMinutes($tokenTtl) : null;
        
        $token = $user->createToken('login', ['*'], $expiresAt);

        return rest($user)->indexMeta([
            'token' => $token->plainTextToken,
            'expires_in' => $tokenTtl ? $tokenTtl * 60 : null,
        ]);
    }
}
