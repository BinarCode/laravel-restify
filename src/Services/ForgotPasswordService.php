<?php

namespace Binaryk\LaravelRestify\Services;

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
        return resolve(static::class)->sendResetLinkEmail($request);
    }
}
