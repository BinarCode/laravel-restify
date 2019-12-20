<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Commands\CheckPassport;
use Binaryk\LaravelRestify\Repositories\Contracts\RestifyRepositoryInterface;
use Binaryk\LaravelRestify\Repositories\RestifyRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelRestifyServiceProvider extends ServiceProvider
{
    /** * @var array
     */
    public $bindings = [
        RestifyRepositoryInterface::class => RestifyRepository::class,
    ];

    /**
     * Bootstrap the application services.
     * @throws \ReflectionException
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets

         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-restify');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-restify');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('restify.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes(

                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-restify'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-restify'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-restify'),
            ], 'lang');*/

            // Registering package commands.
            $this->commands([
                CheckPassport::class,
            ]);
        }

//        $this->resources();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-restify');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-restify', function () {
            return new Restify;
        });
    }

    /**
     * Register the application's Rest resources.
     *
     * @return void
     * @throws \ReflectionException
     */
    protected function resources()
    {
        Restify::resourcesFrom(app_path('Restify'));
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $config = [
            'namespace' => 'Binaryk\LaravelRestify\Http\Controllers',
            'as' => 'restify.api.',
            'prefix' => 'restify-api',
        ];

        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
