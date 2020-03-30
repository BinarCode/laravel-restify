<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;
use Binaryk\LaravelRestify\Exceptions\SanctumUserException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use ReflectionException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthService extends RestifyService
{
    public function login(Request $request)
    {
        if (config('restify.auth.provider') !== 'sanctum') {
            throw SanctumUserException::wrongConfiguration();
        }

        return LoginService::make($request);
    }

    public function register(Request $request)
    {
        return RegisterService::make($request);
    }

    public function forgotPassword(Request $request)
    {
        return ForgotPasswordService::make($request);
    }

    /*
     * @param $id
     * @param null $hash
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     * @throws AuthorizationException
     * @throws EntityNotFoundException
     * @throws PassportUserException
     */
    public function verify($id, $hash = null)
    {
        /**
         * @var Authenticatable
         */
        $user = $this->userQuery()->query()->find($id);

        if ($user instanceof Sanctumable && ! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException('Invalid hash');
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }

    public function resetPassword(Request $request)
    {
        return ResetPasswordService::make($request);
    }

    /**
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Returns query for User model and validate if it exists.
     *
     * @return Model
     * @throws PassportUserException
     * @throws SanctumUserException
     * @throws EntityNotFoundException
     */
    public function userQuery()
    {
        $userClass = Config::get('auth.providers.users.model');
        try {
            $container = Container::getInstance();
            $userInstance = $container->make($userClass);
            $this->validateUserModel($userInstance);

            return $userInstance;
        } catch (BindingResolutionException $e) {
            throw new EntityNotFoundException("The model $userClass from he follow configuration -> 'auth.providers.users.model' cannot be instantiated (may be an abstract class).", $e->getCode(), $e);
        } catch (ReflectionException $e) {
            throw new EntityNotFoundException("The model from the follow configuration -> 'auth.providers.users.model' doesn't exists.", $e->getCode(), $e);
        }
    }

    /**
     * @param $userInstance
     * @throws PassportUserException
     * @throws SanctumUserException
     */
    public function validateUserModel($userInstance)
    {
        if (config('restify.auth.provider') === 'passport' && false === $userInstance instanceof Passportable) {
            throw new PassportUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Passportable contract. User can use 'Laravel\Passport\HasApiTokens' trait"));
        }

        if (config('restify.auth.provider') === 'sanctum' && false === $userInstance instanceof Sanctumable) {
            throw new SanctumUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Sanctumable contract. User should use 'Laravel\Sanctum\HasApiTokens' trait to provide"));
        }
    }

    public function logout()
    {
        return LogoutService::make();
    }
}
