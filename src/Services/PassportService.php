<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Events\UserLogout;
use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;
use Binaryk\LaravelRestify\Exceptions\UnverifiedUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PassportService extends RestifyService
{
    /**
     * Create user token based on credentials
     *
     * @param array $credentials
     * @return string|null
     * @throws CredentialsDoesntMatch
     * @throws PassportUserException
     * @throws UnverifiedUser
     */
    public function createToken(array $credentials = [])
    {
        $token = null;

        if (Auth::attempt($credentials) === false) {
            throw new CredentialsDoesntMatch("Credentials doesn't match");
        }

        /**
         * @var Authenticatable|Passportable|Sanctumable
         */
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail() === false) {
            throw new UnverifiedUser('The email is not verified');
        }

        $this->validateUserModel($user);

        if (method_exists($user, 'createToken')) {
            $token = $user->createToken('Login');
            event(new UserLoggedIn($user));
        }

        return $token;
    }

    /**
     * @param $userInstance
     * @throws PassportUserException
     */
    public function validateUserModel($userInstance)
    {
        if (config('restify.auth.provider') === 'passport' && false === $userInstance instanceof Passportable) {
            throw new PassportUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Passportable contract. User can use 'Laravel\Passport\HasApiTokens' trait"));
        }
    }

    /**
     * Revoke tokens for user.
     *
     * @throws AuthenticatableUserException
     */
    public function logout()
    {
        /**
         * @var User
         */
        $user = Auth::user();

        if ($user instanceof Authenticatable) {
            if ($user instanceof Passportable) {
                $user->tokens()->get()->each->revoke();
                event(new UserLogout($user));
            }
        } else {
            throw new AuthenticatableUserException(__('User is not authenticated.'));
        }
    }
}
