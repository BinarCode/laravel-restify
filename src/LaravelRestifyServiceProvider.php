<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Commands\CheckPassport;
use Binaryk\LaravelRestify\Commands\RepositoryCommand;
use Binaryk\LaravelRestify\Http\Middleware\RestifyInjector;
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
            ]);
            $this->registerPublishing();

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
        // Register the main class to use with the facade
        $this->app->singleton('laravel-restify', function () {
            return new Restify;
        });

        $this->commands([
            RepositoryCommand::class,
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

        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-restify');
        }
    }
}
