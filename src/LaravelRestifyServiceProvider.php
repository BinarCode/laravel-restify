<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Commands\ActionCommand;
use Binaryk\LaravelRestify\Commands\BaseRepositoryCommand;
use Binaryk\LaravelRestify\Commands\CheckPassport;
use Binaryk\LaravelRestify\Commands\DevCommand;
use Binaryk\LaravelRestify\Commands\FilterCommand;
use Binaryk\LaravelRestify\Commands\MatcherCommand;
use Binaryk\LaravelRestify\Commands\PolicyCommand;
use Binaryk\LaravelRestify\Commands\Refresh;
use Binaryk\LaravelRestify\Commands\RepositoryCommand;
use Binaryk\LaravelRestify\Commands\SetupCommand;
use Binaryk\LaravelRestify\Commands\StoreCommand;
use Binaryk\LaravelRestify\Commands\StubCommand;
use Binaryk\LaravelRestify\Http\Middleware\RestifyInjector;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;

class LaravelRestifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckPassport::class,
                SetupCommand::class,
                PolicyCommand::class,
                BaseRepositoryCommand::class,
                Refresh::class,
                StubCommand::class,
            ]);
            $this->registerPublishing();

            $this->app->register(RestifyCustomRoutesProvider::class);
            $this->app->register(RestifyServiceProvider::class);
        }

        /*
         * This will push the RestifyInjector middleware at the end of the middleware stack.
         * This way we could check if the request is really restify related (starts with `config->path for example`)
         * We will load routes and maybe other related resources.
         */
        $this->app->make(HttpKernel::class)->pushMiddleware(RestifyInjector::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        Repository::clearBootedRepositories();

        // Register the main class to use with the facade
        $this->app->singleton('laravel-restify', function () {
            return new Restify;
        });

        $this->commands([
            RepositoryCommand::class,
            ActionCommand::class,
            MatcherCommand::class,
            StoreCommand::class,
            FilterCommand::class,
            DevCommand::class,
        ]);
    }

    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/Commands/stubs/RestifyServiceProvider.stub' => app_path('Providers/RestifyServiceProvider.php'),
        ], 'restify-provider');

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('restify.php'),
        ], 'restify-config');

        $migrationFileName = 'create_action_logs_table.php';
        if (! $this->migrationFileExists($migrationFileName)) {
            $this->publishes([
                __DIR__."/../database/migrations/{$migrationFileName}" => database_path('migrations/'.date('Y_m_d_His', time()).'_'.$migrationFileName),
            ], 'restify-migrations');
        }

        $this->publishes([
            __DIR__.'/../database/' => config_path('restify.php'),
        ], 'restify-config');

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'restify');
    }

    public static function migrationFileExists(string $migrationFileName): bool
    {
        $len = strlen($migrationFileName);
        foreach (glob(database_path('migrations/*.php')) as $filename) {
            if ((substr($filename, -$len) === $migrationFileName)) {
                return true;
            }
        }

        return false;
    }
}
