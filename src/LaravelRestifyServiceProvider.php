<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Commands\BaseRepositoryCommand;
use Binaryk\LaravelRestify\Commands\CheckPassport;
use Binaryk\LaravelRestify\Commands\DocumentationCommand;
use Binaryk\LaravelRestify\Commands\PolicyCommand;
use Binaryk\LaravelRestify\Commands\Refresh;
use Binaryk\LaravelRestify\Commands\RepositoryCommand;
use Binaryk\LaravelRestify\Commands\SetupCommand;
use Binaryk\LaravelRestify\Commands\StubCommand;
use Binaryk\LaravelRestify\Http\Controllers\DocumentationController;
use Binaryk\LaravelRestify\Http\Middleware\RestifyInjector;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
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
                DocumentationCommand::class,
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'restify');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Register the main class to use with the facade
        $this->app->singleton('laravel-restify', function () {
            return new Restify;
        });

        $this->commands([
            RepositoryCommand::class,
        ]);

        $this->registerRoutes();
    }

    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/Commands/stubs/RestifyServiceProvider.stub' => app_path('Providers/RestifyServiceProvider.php'),
        ], 'restify-provider');

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('restify.php'),
        ], 'restify-config');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/skeleton'),
        ], 'restify-views');

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-restify');
        }
    }

    public function registerRoutes()
    {
        Route::macro('restifyDocs', function ($prefix) {
            Route::prefix($prefix)->group(function () {
                Route::get('api-docs', [DocumentationController::class, 'index']);
            });
        });
    }
}
