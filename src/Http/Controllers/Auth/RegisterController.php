<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest;
use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Binaryk\LaravelRestify\Repositories\Serializer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class RegisterController extends Controller
{
    public function __invoke(RestifyRegisterRequest $request): Serializer
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = Config::string('restify.auth.user_model');

        $user = $modelClass::forceCreate([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        /** @var int|numeric-string|null $tokenTtl */
        $tokenTtl = config('restify.auth.token_ttl');
        $expiresAt = $tokenTtl ? now()->addMinutes((int) $tokenTtl) : null;

        $token = $user->createToken('login', ['*'], $expiresAt);

        $meta = [
            'token' => $token->plainTextToken,
            'expires_in' => $tokenTtl ? $tokenTtl * 60 : null,
        ];

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            Notification::send($user, new VerifyEmail);
            $meta['email_verification_sent'] = true;
            $meta['message'] = 'Registration successful. Please check your email to verify your account.';
        }

        return rest($user)->indexMeta($meta);
    }
}
