<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetInvalidTokenException;
use Binaryk\LaravelRestify\Exceptions\UnverifiedUser;
use Binaryk\LaravelRestify\Repositories\Contracts\RestifyRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthService extends RestifyService
{
    public function __construct(RestifyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @param array $credentials
     * @return string|null
     * @throws CredentialsDoesntMatch
     * @throws UnverifiedUser
     * @throws PassportUserException
     */
    public function login(array $credentials = [])
    {
        $token = null;

        if (Auth::attempt($credentials) === false) {
            throw new CredentialsDoesntMatch("Credentials doesn't match");
        }

        /**
         * @var Authenticatable
         */
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail() === false) {
            throw new UnverifiedUser('The email is not verified');
        }

        if ($user instanceof Passportable) {
            $token = $user->createToken('Login')->accessToken;
            event(new UserLoggedIn($user));
        } else {
            throw new PassportUserException(__("User is not implementing Binaryk\LaravelRestify\Contracts\Passportable contract. User can use 'Laravel\Passport\HasApiTokens' trait"));
        }

        return $token;
    }

    /**
     * @param array $payload
     * @throws AuthenticatableUserException
     */
    public function register(array $payload)
    {
        if (false === $this->repository->model() instanceof Authenticatable) {
            throw new AuthenticatableUserException(__("Repository model should be an instance of \Illuminate\Contracts\Auth\Authenticatable"));
        }

        /**
         * @var Authenticatable
         */
        $user = $this->repository->query()->create($payload);

        event(new Registered($user));
    }

    /**
     * @param $id
     * @param null $hash
     * @return Authenticatable
     * @throws AuthorizationException
     */
    public function verify($id, $hash = null)
    {
        /**
         * @var Authenticatable
         */
        $user = $this->repository->query()->find($id);

        if ($user instanceof Passportable && ! hash_equals((string) $hash, sha1($user->getEmail()))) {
            throw new AuthorizationException;
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }

    /**
     * @param $email
     * @return string
     */
    public function sendResetPasswordLinkEmail($email)
    {
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        return $this->broker()->sendResetLink($email);
    }

    /**
     * @param Request $request
     * @param ResetPasswordService $passwordService
     * @return JsonResponse
     * @throws PasswordResetException
     * @throws PasswordResetInvalidTokenException
     */
    public function resetPassword(Request $request, ResetPasswordService $passwordService = null)
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        if (is_null($passwordService)) {
            $passwordService = resolve(ResetPasswordService::class);
            $request->validate($passwordService->rules(), $passwordService->messages());
        }

        $response = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
                $user->password = Hash::make($password);

                $user->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            });

        if ($response === PasswordBroker::INVALID_TOKEN) {
            throw new PasswordResetInvalidTokenException(__('Invalid token.'));
        }

        if ($response !== PasswordBroker::PASSWORD_RESET) {
            throw new PasswordResetException($response);
        }

        return $response;
    }

    /**
     * @return PasswordBroker
     */
    protected function broker()
    {
        return Password::broker();
    }
}
