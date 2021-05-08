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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Mockery;
use Orchestra\Testbench\TestCase;

abstract class IntegrationTest extends TestCase
{
    /**
     * @var User
     */
    protected $authenticatedAs;

    /**
     * @var mixed
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->loadRepositories();
        parent::setUp();
        DB::enableQueryLog();

        Hash::driver('bcrypt')->setRounds(4);

        $this->repositoryMock();
        $this->loadMigrations();
        $this->loadRoutes();
        $this->withFactories(__DIR__.'/Factories');
        $this->injectTranslator();

        Restify::$authUsing = function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Repository::clearResolvedInstances();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelRestifyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
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

    /**
     * Load the migrations for the test environment.
     *
     * @return void
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom([
            '--database' => 'sqlite',
            '--path' => realpath(__DIR__.DIRECTORY_SEPARATOR.'Migrations'),
        ]);
    }

    public function repositoryMock()
    {
    }

    public function injectTranslator()
    {
        $this->instance('translator', (new class implements Translator
        {
            public function get($key, array $replace = [], $locale = null)
            {
                return $key;
            }

            public function choice($key, $number, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * {@inheritdoc}
             */
            public function trans($key, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * {@inheritdoc}
             */
            public function getFromJson($key, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * {@inheritdoc}
             */
            public function transChoice($key, $number, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * {@inheritdoc}
             */
            public function getLocale()
            {
                return 'en';
            }

            /**
             * {@inheritdoc}
             */
            public function setLocale($locale)
            {
            }
        }));
    }

    public function loadRoutes()
    {
        Route::post('login', function () {
            // AuthService->login
        });
        Route::post('register', function () {
            // AuthService -> register
        });
        Route::get('email/verify/{id}/{hash}', function () {
            // AuthService -> verify
        })->name('verification.verify')->middleware([
            'signed',
            'throttle:6,1',
        ]);
        Route::post('password/email', function () {
            // AuthService -> sendResetPasswordLinkEmail
        });
        Route::post('password/reset', function () {
            // AuthPassport -> resetPassword
        })->name('password.reset');
    }

    /**
     * @return array
     */
    public function lastQuery()
    {
        $queries = DB::getQueryLog();

        return end($queries);
    }

    public function loadRepositories(): void
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
    }

    /**
     * Authenticate as an anonymous user.
     * @param  Authenticatable|null  $user
     * @return IntegrationTest
     */
    protected function authenticate(Authenticatable $user = null)
    {
        $this->actingAs($this->authenticatedAs = $user ?? Mockery::mock(Authenticatable::class));

        if (is_null($user)) {
            $this->authenticatedAs->shouldReceive('getAuthIdentifier')->andReturn(1);
            $this->authenticatedAs->shouldReceive('getKey')->andReturn(1);
        }

        return $this;
    }

    /**
     * @param  int  $count
     * @param  array  $predefinedEmails
     * @return \Illuminate\Support\Collection
     */
    public function mockUsers($count = 1, $predefinedEmails = [])
    {
        $users = collect([]);
        $i = 0;
        while ($i < $count) {
            $users->push(factory(User::class)->create());
            $i++;
        }

        foreach ($predefinedEmails as $email) {
            $users->push(factory(User::class)->create([
                'email' => $email,
            ]));
        }

        return $users->shuffle(); // randomly shuffles the items in the collection
    }

    public function mockPosts($userId, $count = 1): Collection
    {
        return Collection::times($count, fn () => factory(Post::class)->create([
            'user_id' => $userId,
        ]))->shuffle();
    }

    public function getTempDirectory($suffix = ''): string
    {
        return __DIR__.'/TestSupport/temp'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getMediaDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/media'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestFilesDirectory($suffix = ''): string
    {
        return $this->getTempDirectory().'/testfiles'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestJpg(): string
    {
        return $this->getTestFilesDirectory('test.jpg');
    }
}
