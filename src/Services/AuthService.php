<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Contracts\Airlockable;
use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Events\UserLogout;
use Binaryk\LaravelRestify\Exceptions\AirlockUserException;
use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetInvalidTokenException;
use Binaryk\LaravelRestify\Exceptions\UnverifiedUser;
use Binaryk\LaravelRestify\Http\Requests\ResetPasswordRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyPasswordEmailRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthService extends RestifyService
{
    /**
     * @var string
     */
    public static $registerFormRequest = RestifyRegisterRequest::class;

    /**
     * The callback that should be used to create the registered user.
     *
     * @var Closure|null
     */
    public static $creating;

    /**
     * @param array $credentials
     * @return string|null
     * @throws CredentialsDoesntMatch
     * @throws UnverifiedUser
     * @throws PassportUserException
     * @throws AirlockUserException
     */
    public function login(array $credentials = [])
    {
        $token = null;

        if (Auth::attempt($credentials) === false) {
            throw new CredentialsDoesntMatch("Credentials doesn't match");
        }

        /**
         * @var Authenticatable|Passportable|Airlockable
         */
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail() === false) {
            throw new UnverifiedUser('The email is not verified');
        }

        $this->validateUserModel($user);

        if (method_exists($user, 'createToken')) {
            $token = $user->createToken('Login')->accessToken;
            event(new UserLoggedIn($user));
        }

        return $token;
    }

    /**
     * @param array $payload
     * @return \Illuminate\Database\Eloquent\Builder|Model|mixed
     * @throws AuthenticatableUserException
     * @throws EntityNotFoundException
     * @throws PassportUserException
     * @throws ValidationException
     * @throws BindingResolutionException
     * @throws AirlockUserException
     *
     */
    public function register(array $payload)
    {
        $this->validateRegister($payload);

        $builder = $this->userQuery();

        if (false === $builder instanceof Authenticatable) {
            throw new AuthenticatableUserException(__("Repository model should be an instance of \Illuminate\Contracts\Auth\Authenticatable"));
        }

        /**
         * @var Authenticatable
         */
        $user = $builder->query()->create(array_merge($payload, [
            'password' => Hash::make(data_get($payload, 'password')),
        ]));

        if ($user instanceof Authenticatable) {
            event(new Registered($user));
        }

        return $user;
    }

    /**
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

        if ($user instanceof Passportable && ! hash_equals((string) $hash, sha1($user->getEmail()))) {
            throw new AuthorizationException('Invalid hash');
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }

    /**
     * @param $email
     * @return string
     * @throws EntityNotFoundException
     * @throws PasswordResetInvalidTokenException
     * @throws ValidationException
     * @throws PasswordResetException
     */
    public function sendResetPasswordLinkEmail($email)
    {
        $validator = Validator::make(compact('email'), (new RestifyPasswordEmailRequest)->rules(), (new RestifyPasswordEmailRequest)->messages());
        if ($validator->fails()) {
            // this is manually thrown for readability
            throw new ValidationException($validator);
        }
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(compact('email'));
        $this->resolveBrokerResponse($response, PasswordBroker::RESET_LINK_SENT, PasswordBroker::PASSWORD_RESET);

        return $response;
    }

    /**
     * @param array $credentials
     * @return JsonResponse
     * @throws PasswordResetInvalidTokenException
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @throws PasswordResetException
     */
    public function resetPassword(array $credentials = [])
    {
        $validator = Validator::make($credentials, (new ResetPasswordRequest())->rules(), (new ResetPasswordRequest())->messages());
        if ($validator->fails()) {
            // this is manually thrown for readability
            throw new ValidationException($validator);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $credentials, function ($user, $password) {
                $user->password = Hash::make($password);

                $user->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            });

        $this->resolveBrokerResponse($response, PasswordBroker::PASSWORD_RESET);

        return $response;
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
     * @throws EntityNotFoundException
     * @throws PassportUserException
     * @throws AirlockUserException
     * @return Model
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
     * @throws AirlockUserException
     */
    public function validateUserModel($userInstance)
    {
        if (config('restify.auth.provider') === 'passport' && false === $userInstance instanceof Passportable) {
            throw new PassportUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Passportable contract. User can use 'Laravel\Passport\HasApiTokens' trait"));
        }

        if (config('restify.auth.provider') === 'airlock' && false === $userInstance instanceof Airlockable) {
            throw new AirlockUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Airlockable contract. User should use 'Laravel\Airlock\HasApiTokens' trait to provide"));
        }
    }

    /**
     * @param $response
     * @param null $case
     * @throws EntityNotFoundException
     * @throws PasswordResetException
     * @throws PasswordResetInvalidTokenException
     */
    protected function resolveBrokerResponse($response, $case = null)
    {
        if ($response === PasswordBroker::INVALID_TOKEN) {
            throw new PasswordResetInvalidTokenException(__('Invalid token.'));
        }

        if ($response === PasswordBroker::INVALID_USER) {
            throw new EntityNotFoundException(__("User with provided email doesn't exists."));
        }
        if ($case && $response !== $case) {
            throw new PasswordResetException($response);
        }
    }

    /**
     * @param array $payload
     * @return bool
     * @throws ValidationException
     * @throws BindingResolutionException
     */
    public function validateRegister(array $payload)
    {
        try {
            if (class_exists(static::$registerFormRequest) && (new \ReflectionClass(static::$registerFormRequest))->isInstantiable()) {
                $validator = Validator::make($payload, (new static::$registerFormRequest)->rules(), (new static::$registerFormRequest)->messages());
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        } catch (ReflectionException $e) {
            $concrete = static::$registerFormRequest;
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        return true;
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
                $user->tokens->each->revoke();
                event(new UserLogout($user));
            }

            if ($user instanceof Airlockable) {
                $user->tokens->each->delete();
                event(new UserLogout($user));
            }
        } else {
            throw new AuthenticatableUserException(__('User is not authenticated.'));
        }
    }
}
