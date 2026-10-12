<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Http\Controllers\Concerns\FindsUserByEmail;
use Binaryk\LaravelRestify\Repositories\Serializer;
use Binaryk\LaravelRestify\Services\Auth\TokenTtlResolver;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use FindsUserByEmail;

    public function __invoke(Request $request): Serializer
    {
        /** @var array{email: string, password: string|int|float|bool} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', self::scalarPasswordRule()],
        ]);

        /** @var class-string<Model&Authenticatable&Sanctumable> $userModel */
        $userModel = config('restify.auth.user_model');

        $user = $this->findUserByEmail($userModel, $credentials['email']);

        if (! $user) {
            abort(JsonResponse::HTTP_UNAUTHORIZED, 'Invalid credentials.');
        }

        if (! Hash::check((string) $credentials['password'], $user->getAuthPassword())) {
            abort(JsonResponse::HTTP_UNAUTHORIZED, 'Invalid credentials.');
        }

        $ttlSeconds = TokenTtlResolver::seconds(config('restify.auth.token_ttl'));

        Auth::login($user);

        $expiresAt = $ttlSeconds !== null ? now()->addSeconds($ttlSeconds) : null;

        $token = $user->createToken('login', ['*'], $expiresAt);

        return rest($user)->withoutHiddenAttributes()->indexMeta([
            'token' => $token->plainTextToken,
            'expires_in' => $ttlSeconds,
        ]);
    }

    /**
     * A non-scalar password (array/object) can't be hashed - reject it with
     * a validation error instead of reaching Hash::check() and crashing.
     */
    protected static function scalarPasswordRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_scalar($value)) {
                $fail(__('The :attribute must be a string.'));
            }
        };
    }
}
