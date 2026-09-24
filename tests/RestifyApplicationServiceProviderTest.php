<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests;

use App\Providers\RestifyServiceProvider;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Closure;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;
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
    public function a_guest_in_the_local_environment_is_authorized_by_the_default_gate(): void
    {
        $this->app['env'] = 'local';

        $this->app['auth']->forgetGuards();

        $this->getJson(UserRepository::route())
            ->assertOk();
    }

    #[Test]
    public function a_guest_passes_a_consumer_gate_that_types_the_user_as_nullable(): void
    {
        Gate::define('viewRestify', fn (?User $user): bool => true);

        $this->app['auth']->forgetGuards();

        $this->getJson(UserRepository::route())
            ->assertOk();
    }

    #[Test]
    public function a_guest_passes_a_consumer_gate_that_defaults_the_user_to_null(): void
    {
        Gate::define('viewRestify', function ($user = null): bool {
            return true;
        });

        $this->app['auth']->forgetGuards();

        $this->getJson(UserRepository::route())
            ->assertOk();
    }

    #[Test]
    public function the_published_stubs_gate_closure_denies_a_guest_with_401_instead_of_a_500(): void
    {
        $stubPath = dirname((new ReflectionClass(RestifyApplicationServiceProvider::class))->getFileName())
            .'/Commands/stubs/RestifyServiceProvider.stub';

        if (! class_exists(RestifyServiceProvider::class, false)) {
            require $stubPath;
        }

        $provider = new RestifyServiceProvider($this->app);

        (new ReflectionMethod($provider, 'gate'))->invoke($provider);

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
        Gate::define('viewRestify', function (User $user): bool {
            return in_array($user->email, ['allowed@example.com'], true);
        });

        $this->authenticate(User::factory()->create(['email' => $userEmail]));

        $response = $this->getJson(UserRepository::route());

        $expectAuthorized ? $response->assertOk() : $response->assertUnauthorized();
    }

    #[Test]
    public function a_user_policys_view_restify_method_does_not_hijack_the_gate(): void
    {
        $policy = new class extends UserPolicy
        {
            public function viewRestify(User $user): bool
            {
                return true;
            }
        };

        $this->app->instance($policy::class, $policy);
        Gate::policy(User::class, $policy::class);

        Gate::define('viewRestify', fn (User $user): bool => false);

        $this->authenticate(User::factory()->create());

        $this->getJson(UserRepository::route())
            ->assertUnauthorized();
    }
}
