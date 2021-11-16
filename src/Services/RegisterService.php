<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest;
use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionException;

class RegisterService
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * The callback that should be used to create the registered user.
     *
     * @var Closure|null
     */
    public static $creating;

    public static $registerFormRequest = RestifyRegisterRequest::class;

    public function register(Request $request)
    {
        $payload = $request->all();

        $this->validateRegister($payload);

        $builder = $this->authService->userQuery();

        if (false === $builder instanceof Authenticatable) {
            throw AuthenticatableUserException::wrongInstance();
        }

        $userData = array_merge($payload, [
            'password' => Hash::make(data_get($payload, 'password')),
        ]);

        if (is_callable(static::$creating)) {
            $user = call_user_func(static::$creating, $userData);
        } else {
            $user = $builder->query()->create($userData);
        }

        if ($user instanceof Authenticatable) {
            event(new Registered($user));
        }

        return $user;
    }

    public static function make(Request $request, AuthService $authService)
    {
        return resolve(static::class)
            ->usingAuthService($authService)
            ->register($request);
    }

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

    protected function usingAuthService(AuthService $service)
    {
        $this->authService = $service;

        return $this;
    }
}
