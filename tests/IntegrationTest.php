<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\LaravelRestifyServiceProvider;
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
        $this->withFactories(__DIR__.'/Factories');
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
            '--realpath' => realpath(__DIR__.'/Migrations'),
        ]);
    }

    public function repositoryMock()
    {
    }

    public function injectTranslator()
    {
        $this->app->instance('translator', new class {
            public function getFromJson($key)
            {
                return $key;
            }
        });
    }
}
