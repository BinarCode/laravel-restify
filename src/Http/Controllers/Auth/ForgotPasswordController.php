<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Notifications\ForgotPasswordNotification;
use Binaryk\LaravelRestify\Validation\Rules\AllowedResetUrlHost;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'url' => ['sometimes', 'string', 'max:2048', AllowedResetUrlHost::fromConfig()],
        ]);

        /** @var class-string<Model&CanResetPassword> $userModel */
        $userModel = config('restify.auth.user_model');

        $user = $userModel::query()->where($request->only('email'))->first();

        if ($user !== null) {
            try {
                $this->sendResetLinkTo($user, $request);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return ok(__('Reset password link sent to your email.'));
    }

    private function sendResetLinkTo(CanResetPassword $user, Request $request): void
    {
        $token = Password::createToken($user);

        /** @var string $urlTemplate */
        $urlTemplate = $request->input('url') ?? config('restify.auth.password_reset_url');

        $url = str_replace(
            ['{token}', '{email}'],
            [rawurlencode($token), rawurlencode($user->getEmailForPasswordReset())],
            $urlTemplate
        );

        (new AnonymousNotifiable)->route('mail', $user->getEmailForPasswordReset())->notify(new ForgotPasswordNotification($url));
    }
}
