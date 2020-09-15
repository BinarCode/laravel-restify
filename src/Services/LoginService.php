<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class LoginService
{
    use AuthenticatesUsers;

    public static function make(Request $request)
    {
        return resolve(static::class)->login($request);
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if (($user = $this->guard()->user()) instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
                return abort(401, 'User must verify email.');
            }

            event(new UserLoggedIn($this->guard()->user()));

            return $this->guard()->user()->createToken('login');
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
