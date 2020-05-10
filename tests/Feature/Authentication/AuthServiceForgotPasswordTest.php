<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Authentication;

use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Notifications\PasswordResetNotification;
use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Services\RegisterService;
use Binaryk\LaravelRestify\Tests\Fixtures\MailTracking;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthServiceForgotPasswordTest extends IntegrationTest
{
    use MailTracking,
        InteractsWithContainer;

    /**
     * @var AuthService
     */
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailTracking();
        RegisterService::$registerFormRequest = null;
        $this->authService = resolve(AuthService::class);
        $this->app['config']->set('restify.auth.provider', 'sanctum');
        $this->app['config']->set('restify.auth.frontend_app_url', 'https://laravel-restify.dev');
        $this->app['config']->set('restify.auth.password_reset_url', 'https://laravel-restify.dev/password/reset?token={token}&email={email}');
    }

    public function test_email_was_sent_and_contain_token()
    {
        Notification::fake();

        $user = $this->register();
        $request = new Request([], []);
        $request->merge(['email' => $user->email]);

        $this->authService->forgotPassword($request);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $this->assertNotEmpty($notification->token);
            return true;
        });
    }

    public function test_reset_password_invalid_payload()
    {
        $this->expectException(ValidationException::class);
        $request = new Request([], []);
        $request->merge([
            'email' => null,
            'password' => 'password',
            'password_confirmation' => 'password',
            'token' => 'secret',
        ]);
        $this->authService->resetPassword($request);
    }

    public function test_reset_password_successfully()
    {
        Notification::fake();
        $user = $this->register();
        $this->authService->verify($user->id, sha1($user->email));

        $request = new Request([], []);
        $request->merge(['email' => $user->email]);

        $this->authService->forgotPassword($request);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $token = $notification->token;
            $password = Str::random(10);

            $request = new Request([], []);
            $request->merge([
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ]);

            $this->authService->resetPassword($request);

            Event::assertDispatched(PasswordReset::class, function ($e) use ($user) {
                $this->assertEquals($e->user->email, $user->email);

                return $e->user instanceof User;
            });

            $request = new Request([], []);
            $request->merge([
                'email' => $user->email,
                'password' => $password,
            ]);

            $this->authService->login($request);

            Event::assertDispatched(UserLoggedIn::class, function ($e) use ($user) {
                $this->assertEquals($e->user->email, $user->email);

                return $e->user instanceof User;
            });

            return true;
        });
    }

    public function register()
    {
        Event::fake([
            Registered::class,
            PasswordReset::class,
            UserLoggedIn::class,
        ]);

        $this->app->instance(User::class, new User);

        $request = new Request([], []);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'secret!',
            'remember_token' => Str::random(10),
        ];

        $request->merge($user);

        $this->authService->register($request);

        return User::query()->get()->last();
    }
}
