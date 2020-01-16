<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

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

        $this->customDefinitions()
            ->defaultRoutes($config);
    }

    /**
     * @return RestifyServiceProvider
     */
    public function customDefinitions()
    {
        collect(Restify::$repositories)->each(function ($repository) {
            $config = [
                'namespace' => trim(app()->getNamespace(), '\\').'\Http\Controllers',
                'as' => '',
                'prefix' => Restify::path($repository::uriKey()),
                'middleware' => config('restify.middleware', []),
            ];

            $reflector = new ReflectionClass($repository);

            $method = $reflector->getMethod('routes');

            $parameters = $method->getParameters();

            if (count($parameters) === 2 && $parameters[1] instanceof \ReflectionParameter) {
                $config = array_merge($config, $parameters[1]->getDefaultValue());
            }

            Route::group($config, function ($router) use ($repository) {
                $repository::routes($router);
            });
        });

        return $this;
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
