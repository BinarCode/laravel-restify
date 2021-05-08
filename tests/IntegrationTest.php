<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostAuthorizeRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostUnauthorizedFieldRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithHiddenFieldRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithUnauthorizedFieldsRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use JetBrains\PhpStorm\Pure;
use Mockery;
use Orchestra\Testbench\TestCase;

abstract class IntegrationTest extends TestCase
{
    protected Mockery\MockInterface|User $authenticatedAs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadRepositories()
            ->loadMigrations()
            ->withFactories(__DIR__.'/Factories');

        Restify::$authUsing = static function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

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
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('restify.base', '/');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__.'/../database/migrations/create_action_logs_table.php';
        (new \CreateActionLogsTable())->up();
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
            PostMergeableRepository::class,
            PostAuthorizeRepository::class,
            PostWithUnauthorizedFieldsRepository::class,
            PostUnauthorizedFieldRepository::class,
            PostWithHiddenFieldRepository::class,
            RoleRepository::class,
        ]);

        return $this;
    }

    protected function authenticate(Authenticatable $user = null)
    {
        $this->actingAs($this->authenticatedAs = $user ?? Mockery::mock(Authenticatable::class));

        if (is_null($user)) {
            $this->authenticatedAs->shouldReceive('getAuthIdentifier')->andReturn(1);
            $this->authenticatedAs->shouldReceive('getKey')->andReturn(1);
        }

        return $this;
    }

    public function mockUsers($count = 1, array $predefinedEmails = []): Collection
    {
        return Collection::times($count, fn($i) => factory(User::class)->create())
            ->merge(collect($predefinedEmails)->each(fn(string $email) => factory(User::class)->create([
                'email' => $email
            ])))
            ->shuffle();
    }

    public function mockPosts($userId, $count = 1): Collection
    {
        return Collection::times($count, fn() => factory(Post::class)->create([
            'user_id' => $userId,
        ]))->shuffle();
    }

    public function getTempDirectory($suffix = ''): string
    {
        return __DIR__.'/TestSupport/temp'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure] public function getMediaDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/media'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure] public function getTestFilesDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/testfiles'.($suffix === '' ? '' : '/'.$suffix);
    }

    #[Pure] public function getTestJpg(): string
    {
        return $this->getTestFilesDirectory('test.jpg');
    }
}
