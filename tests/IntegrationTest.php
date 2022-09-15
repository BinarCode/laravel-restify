<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Models\ActionLogPolicy;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyApplicationServiceProvider;
use Binaryk\LaravelRestify\Tests\Concerns\Mockers;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithHiddenFieldRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Prototypes;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RolePolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\MockUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use JetBrains\PhpStorm\Pure;
use Mockery;
use Orchestra\Testbench\TestCase;

abstract class IntegrationTest extends TestCase
{
    use Mockers;
    use Prototypes;

    protected Mockery\MockInterface|User|null $authenticatedAs = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->repositories()
            ->policies()
            ->migrations();

        Factory::guessFactoryNamesUsing(
            fn (
                string $modelName
            ) => 'Binaryk\\LaravelRestify\\Tests\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Restify::$authUsing = static function () {
            return true;
        };

        $this->ensureLoggedIn();
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
            RestifyApplicationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('restify.auth.user_model', User::class);
        config()->set('restify.repositories.serialize_index_meta', true);
        config()->set('restify.repositories.serialize_show_meta', true);

        $migration = include __DIR__.'/../database/migrations/create_action_logs_table.php.stub';
        $migration->up();
    }

    private function migrations(): self
    {
        $this->loadMigrationsFrom([
            '--database' => 'sqlite',
            '--path' => realpath(__DIR__.'/database/migrations'),
        ]);

        return $this;
    }

    public function repositories(): self
    {
        Restify::repositories([
            UserRepository::class,
            PostRepository::class,
            CompanyRepository::class,
            PostWithHiddenFieldRepository::class,
            RoleRepository::class,
            CommentRepository::class,
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
        Gate::policy(Comment::class, CommentPolicy::class);

        return $this;
    }

    protected function ensureLoggedIn(): self
    {
        if (is_null($this->authenticatedAs)) {
            $this->authenticate();
        }

        return $this;
    }

    protected function logout(): self
    {
        if ($this->authenticatedAs instanceof Mockery\MockInterface) {
            $this->authenticatedAs->shouldReceive('getRememberToken')->andReturnNull();
        }

        $this->app['auth']->guard()->logout();
        $this->authenticatedAs = null;

        return $this;
    }
}
