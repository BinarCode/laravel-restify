<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class RestifyApplicationServiceProviderTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new ReflectionMethod(RestifyApplicationServiceProvider::class, 'authorization'))
            ->invoke(new RestifyApplicationServiceProvider($this->app));
    }

    #[Test]
    public function a_guest_is_denied_with_401_instead_of_a_500(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson(UserRepository::route())
            ->assertStatus(401);
    }

    #[Test]
    public function a_user_whose_email_is_not_allowed_is_denied(): void
    {
        Gate::define('viewRestify', function (Authenticatable $user) {
            return in_array($user->email, ['allowed@example.com'], true);
        });

        $this->authenticate(User::factory()->create(['email' => 'denied@example.com']));

        $this->getJson(UserRepository::route())
            ->assertStatus(401);
    }

    #[Test]
    public function a_user_whose_email_is_allowed_passes(): void
    {
        Gate::define('viewRestify', function (Authenticatable $user) {
            return in_array($user->email, ['allowed@example.com'], true);
        });

        $this->authenticate(User::factory()->create(['email' => 'allowed@example.com']));

        $this->getJson(UserRepository::route())
            ->assertStatus(200);
    }
}
