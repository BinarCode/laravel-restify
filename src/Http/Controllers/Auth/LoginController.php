<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Repositories\Serializer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(Request $request): Serializer
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var class-string<Model&Authenticatable&Sanctumable> $userModel */
        $userModel = config('restify.auth.user_model');

        $user = $userModel::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user) {
            abort(JsonResponse::HTTP_UNAUTHORIZED, 'Invalid credentials.');
        }

        if (! Hash::check($credentials['password'], $user->getAuthPassword())) {
            abort(JsonResponse::HTTP_UNAUTHORIZED, 'Invalid credentials.');
        }

        Auth::login($user);

        /** @var int|float|numeric-string|null $tokenTtl */
        $tokenTtl = config('restify.auth.token_ttl');
        $ttlSeconds = is_numeric($tokenTtl) && (float) $tokenTtl > 0
            ? (int) round((float) $tokenTtl * 60)
            : null;
        $expiresAt = $ttlSeconds ? now()->addSeconds($ttlSeconds) : null;

        $token = $user->createToken('login', ['*'], $expiresAt);

        return rest($user)->indexMeta([
            'token' => $token->plainTextToken,
            'expires_in' => $ttlSeconds,
        ]);
    }
}
