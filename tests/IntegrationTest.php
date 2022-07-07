<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Models\ActionLogPolicy;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithHiddenFieldRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RolePolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\MockUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use JetBrains\PhpStorm\Pure;
use Mockery;
use Orchestra\Testbench\TestCase;

abstract class IntegrationTest extends TestCase
{
    protected Mockery\MockInterface|User|null $authenticatedAs = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->loadRepositories()
            ->policies()
            ->loadMigrations();

        config()->set('restify.auth.user_model', User::class);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Binaryk\\LaravelRestify\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );

        Restify::$authUsing = static function () {
            return true;
        };

        $this->ensureLoggedIn();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
        Repository::clearResolvedInstances();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelRestifyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'sqlite');

        $migration = include __DIR__.'/../database/migrations/create_action_logs_table.php.stub';
        $migration->up();
    }

    protected function loadMigrations(): self
    {
        $this->loadMigrationsFrom([
            '--database' => 'sqlite',
            '--path' => realpath(__DIR__.DIRECTORY_SEPARATOR.'Migrations'),
        ]);

        return $this;
    }

    public function loadRepositories(): self
    {
        Restify::repositories([
            UserRepository::class,
            PostRepository::class,
            CompanyRepository::class,
            PostWithHiddenFieldRepository::class,
            RoleRepository::class,
        ]);

        return $this;
    }

    protected function authenticate(Authenticatable $user = null): self
    {
        $this->actingAs($this->authenticatedAs = $user ?? Mockery::mock(MockUser::class));

        if (is_null($user)) {
            $this->authenticatedAs->shouldReceive('getAuthIdentifier')->andReturn(1);
            $this->authenticatedAs->shouldReceive('getKey')->andReturn(1);
        }

        return $this;
    }

    public function mockUsers($count = 1, array $predefinedEmails = []): Collection
    {
        return Collection::times($count, fn ($i) => User::factory()->create())
            ->merge(collect($predefinedEmails)->each(fn (string $email) => User::factory()->create([
                'email' => $email,
            ])))
            ->shuffle();
    }

    public function mockPosts($userId = null, $count = 1): Collection
    {
        return Collection::times($count, fn () => Post::factory()->create([
            'user_id' => $userId,
        ]))->shuffle();
    }

    protected function mockPost(array $attributes = []): Post
    {
        return Post::factory()->create($attributes);
    }

    public function getTempDirectory($suffix = ''): string
    {
        return __DIR__.'/TestSupport/temp'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure]
    public function getMediaDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/media'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure]
    public function getTestFilesDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/testfiles'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure]
    public function getTestJpg(): string
    {
        return $this->getTestFilesDirectory('test.jpg');
    }

    private function policies(): self
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(ActionLog::class, ActionLogPolicy::class);

        return $this;
    }

    protected function ensureLoggedIn(): self
    {
        if (is_null($this->authenticatedAs)) {
            $this->authenticate();
        }

        return $this;
    }
}
