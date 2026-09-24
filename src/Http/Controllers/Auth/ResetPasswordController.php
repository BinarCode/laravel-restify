<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Timebox;

class ResetPasswordController extends Controller
{
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
            /** @var class-string<Model&CanResetPassword> $userModel */
            $userModel = config('restify.auth.user_model');

            $user = $userModel::query()->where($request->only('email'))->first();

            if ($user === null || ! Password::getRepository()->exists($user, $token)) {
                abort(JsonResponse::HTTP_BAD_REQUEST, __('Provided invalid token.'));
            }

            $user->forceFill(['password' => Hash::make($password)])->save();

            Password::deleteToken($user);

            return ok(__('Your password has been successfully reset.'));
        }, $timeboxDuration);
    }
}
