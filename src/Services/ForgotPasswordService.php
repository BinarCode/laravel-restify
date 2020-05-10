<?php

namespace Binaryk\LaravelRestify\Services;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ForgotPasswordService
{
    use SendsPasswordResetEmails;

    public static function make(Request $request)
    {
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $withToken = str_replace(['{token}'], $token, config('restify.auth.password_reset_url'));
            $withEmail = str_replace(['{email}'], $notifiable->getEmailForPasswordReset(), $withToken);

            return url($withEmail);
        });

        return resolve(static::class)->sendResetLinkEmail($request);
    }
}
