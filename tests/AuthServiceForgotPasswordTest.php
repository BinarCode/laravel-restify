<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthServiceForgotPasswordTest extends IntegrationTest
{
    /**
     * @var AuthService
     */
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = resolve(AuthService::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    public function test_register_throw_user_not_authenticatable()
    {
        Event::fake([
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
            'remember_token' => Str::random(10),
        ];

        $this->authService->register($user);

        Event::assertDispatched(Registered::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user['email']);

            return $e->user instanceof \Binaryk\LaravelRestify\Tests\Fixtures\User;
        });

        $lastUser = User::query()->get()->last();

        $this->assertEquals($lastUser->email, $user['email']);
    }

    public function test_verify_user_throw_hash_not_match()
    {
        Event::fake([
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
            'remember_token' => Str::random(10),
        ];

        $this->authService->register($user);
        $lastUser = User::query()->get()->last();

        $this->expectException(AuthorizationException::class);
        $this->authService->verify($lastUser->id, sha1('random@email.com'));
    }

    public function test_verify_user_successfully()
    {
        Event::fake([
            Verified::class,
            Registered::class,
        ]);

        $this->app->instance(User::class, new User);

        $user = [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
            'remember_token' => Str::random(10),
        ];

        $this->authService->register($user);
        $lastUser = User::query()->get()->last();

        $this->assertNull($lastUser->email_verified_at);
        $this->authService->verify($lastUser->id, sha1('eduard.lupacescu@binarcode.com'));
        $lastUser->refresh();
        $this->assertNotNull($lastUser->email_verified_at);
        Event::assertDispatched(Verified::class, function ($e) use ($user) {
            $this->assertEquals($e->user->email, $user['email']);
            return $e->user instanceof \Binaryk\LaravelRestify\Tests\Fixtures\User;
        });
    }
}
