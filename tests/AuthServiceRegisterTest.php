<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * @package Binaryk\LaravelRestify\Tests;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class AuthServiceRegisterTest extends IntegrationTest
{
    /**
     * @var AuthService $authService
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
}
