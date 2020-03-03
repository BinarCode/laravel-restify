<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetException;
use Binaryk\LaravelRestify\Exceptions\PasswordResetInvalidTokenException;
use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Tests\Fixtures\MailTracking;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
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
        AuthService::$registerFormRequest = null;
        $this->authService = resolve(AuthService::class);
    }

    public function test_invalid_email_in_payload()
    {
        $this->expectException(ValidationException::class);
        $this->authService->sendResetPasswordLinkEmail('invalid_email');
    }

    public function test_email_was_sent_and_contain_token()
    {
        $user = $this->register();
        $this->authService->sendResetPasswordLinkEmail($user->email);
        $lastEmail = $this->lastEmail()->getBody();
        preg_match_all('/token=([\w\.]*)/i', $lastEmail, $data);
        $token = $data[1][0];

        $this->assertEmailsSent(1);
        $this->assertEmailTo($user->email);
        $this->assertNotNull($token);
    }

    public function test_reset_password_invalid_payload()
    {
        $this->expectException(ValidationException::class);
        $this->authService->resetPassword([
            'email' => null,
            'password' => 'password',
            'password_confirmation' => 'password',
            'token' => 'secret',
        ]);
    }

    public function test_reset_password_invalid_token()
    {
        $user = $this->register();
        $this->expectException(PasswordResetInvalidTokenException::class);
        $this->authService->resetPassword([
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'token' => 'secret',
        ]);
    }

    public function test_reset_password_invalid_user()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->authService->resetPassword([
            'email' => 'random@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'token' => 'secret',
        ]);
    }

    public function test_reset_password_successfully()
    {
        $user = $this->register();
        $this->authService->verify($user->id, sha1($user->email));
        $this->authService->sendResetPasswordLinkEmail($user->email);
        $lastEmail = $this->lastEmail()->getBody();
        preg_match_all('/token=([\w\.]*)/i', $lastEmail, $data);
        $token = $data[1][0];
        $password = Str::random(10);

        $this->authService->resetPassword([
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
            'token' => $token,
        ]);

        Event::assertDispatched(PasswordReset::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user->email);

            return $e->user instanceof User;
        });

        $this->authService->login([
            'email' => $user->email,
            'password' => $password,
        ]);

        Event::assertDispatched(UserLoggedIn::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user->email);

            return $e->user instanceof User;
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

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
            'remember_token' => Str::random(10),
        ];

        $this->authService->register($user);

        return User::query()->get()->last();
    }
}
