<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\FindsUserByEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

class ResetPasswordController extends Controller
{
    use FindsUserByEmail;

    public const int REMEMBER_TOKEN_LENGTH = 60;

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $token = $request->string('token')->toString();
        $password = $request->string('password')->toString();

        /** @var int $timeboxDuration */
        $timeboxDuration = config('restify.auth.password_reset_timebox');

        return app(Timebox::class)->call(function () use ($request, $token, $password): JsonResponse {
            /** @var class-string<Model&CanResetPassword&Authenticatable> $userModel */
            $userModel = config('restify.auth.user_model');

            $user = $this->findUserByEmail($userModel, $request->string('email')->toString());

            if ($user === null || ! Password::getRepository()->exists($user, $token)) {
                abort(JsonResponse::HTTP_BAD_REQUEST, __('Provided invalid token.'));
            }

            $user->getConnection()->transaction(function () use ($user, $password): void {
                $user->forceFill([$user->getAuthPasswordName() => Hash::make($password)]);
                $user->setRememberToken(Str::random(self::REMEMBER_TOKEN_LENGTH));
                $user->save();

                Password::deleteToken($user);

                if (config('restify.auth.revoke_tokens_on_reset', true)) {
                    $this->revokeApiTokens($user);
                }
            });

            event(new PasswordReset($user));

            return ok(__('Your password has been successfully reset.'));
        }, $timeboxDuration);
    }

    private function revokeApiTokens(Model $user): void
    {
        if (! $user instanceof HasApiTokensContract && ! in_array(HasApiTokens::class, class_uses_recursive($user), true)) {
            return;
        }

        /** @var Model&HasApiTokensContract $user */
        $user->tokens()->delete();
    }
}
