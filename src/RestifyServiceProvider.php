<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * This provider is injected in console context by the main provider or by the RestifyInjector
 * if a restify request.
 */
class RestifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerRoutes();
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
            'prefix' => Restify::path(),
            'middleware' => config('restify.middleware', []),
        ];

        $this->defaultRoutes($config);
    }

    /**
     * @param $config
     * @return RestifyServiceProvider
     */
    public function defaultRoutes($config)
    {
        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });

        return $this;
    }
}
