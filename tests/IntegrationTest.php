<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class IntegrationTest extends TestCase
{
    /**
     * @var mixed
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Hash::driver('bcrypt')->setRounds(4);
        $this->repositoryMock();
        $this->loadMigrations();
        $this->withFactories(__DIR__ . '/Factories');
        $this->injectTranslator();
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

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
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
            '--realpath' => realpath(__DIR__ . '/Migrations'),
        ]);
    }

    public function repositoryMock()
    {
    }

    public function injectTranslator()
    {
        $this->instance('translator', (new class implements Translator {

            /**
             * @inheritDoc
             */
            public function trans($key, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * @inheritDoc
             */
            public function getFromJson($key, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * @inheritDoc
             */
            public function transChoice($key, $number, array $replace = [], $locale = null)
            {
                return $key;
            }

            /**
             * @inheritDoc
             */
            public function getLocale()
            {
                return 'en';
            }

            /**
             * @inheritDoc
             */
            public function setLocale($locale)
            {

            }
        }));
    }
}
