<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:'.Config::get('config.auth.table', 'users')],
            'password' => ['required', 'confirmed'],
        ]);

        $model = config('restify.auth.user_model');

        $user = $model::forceCreate([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $tokenTtl = config('restify.auth.token_ttl');
        $expiresAt = $tokenTtl ? now()->addMinutes($tokenTtl) : null;
        
        $token = $user->createToken('login', ['*'], $expiresAt);
        
        $meta = [
            'token' => $token->plainTextToken,
            'expires_in' => $tokenTtl ? $tokenTtl * 60 : null,
        ];
        
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->notify(new VerifyEmail);
            $meta['email_verification_sent'] = true;
            $meta['message'] = 'Registration successful. Please check your email to verify your account.';
        }

        return rest($user)->indexMeta($meta);
    }
}
