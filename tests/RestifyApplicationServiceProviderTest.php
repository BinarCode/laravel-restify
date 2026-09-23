<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;

class RestifyApplicationServiceProviderTest extends IntegrationTestCase
{
    private ?Closure $originalAuthUsing;

    private string $originalEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAuthUsing = Restify::$authUsing;
        $this->originalEnvironment = $this->app['env'];

        $this->app['env'] = 'production';

        (new ReflectionMethod(RestifyApplicationServiceProvider::class, 'authorization'))
            ->invoke($this->app->getProvider(RestifyApplicationServiceProvider::class));
    }

    protected function tearDown(): void
    {
        Restify::$authUsing = $this->originalAuthUsing;
        $this->app['env'] = $this->originalEnvironment;

        parent::tearDown();
    }

    #[Test]
    public function a_guest_is_denied_with_401_instead_of_a_500(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson(UserRepository::route())
            ->assertUnauthorized();
    }

    #[Test]
    public function the_default_gate_denies_a_real_eloquent_user_whose_email_is_not_configured(): void
    {
        $this->authenticate(User::factory()->create());

        $this->getJson(UserRepository::route())
            ->assertUnauthorized();
    }

    #[Test]
    #[TestWith(['allowed@example.com', true], 'email in the list passes')]
    #[TestWith(['denied@example.com', false], 'email not in the list is denied')]
    public function a_user_is_authorized_only_when_their_email_is_in_the_gates_list(string $userEmail, bool $expectAuthorized): void
    {
        Gate::define('viewRestify', function (Authenticatable $user) {
            return in_array($user->email, ['allowed@example.com'], true);
        });

        $this->authenticate(User::factory()->create(['email' => $userEmail]));

        $response = $this->getJson(UserRepository::route());

        $expectAuthorized ? $response->assertOk() : $response->assertUnauthorized();
    }

    #[Test]
    public function a_user_policys_view_restify_method_does_not_hijack_the_gate(): void
    {
        Gate::policy(User::class, new class
        {
            public function viewRestify(User $user): bool
            {
                return true;
            }
        });

        Gate::define('viewRestify', fn (Authenticatable $user) => false);

        $this->authenticate(User::factory()->create());

        $this->getJson(UserRepository::route())
            ->assertUnauthorized();
    }
}
