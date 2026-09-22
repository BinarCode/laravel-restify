<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        /** @var class-string<Model&CanResetPassword> $userModel */
        $userModel = config('restify.auth.user_model');

        $user = $userModel::query()->where($request->only('email'))->first();

        if ($user === null || ! Password::getRepository()->exists($user, $request->input('token'))) {
            abort(JsonResponse::HTTP_BAD_REQUEST, 'Provided invalid token.');
        }

        $user->forceFill(['password' => Hash::make($request->input('password'))])->save();

        Password::deleteToken($user);

        return ok('Your password has been successfully reset.');
    }
}
