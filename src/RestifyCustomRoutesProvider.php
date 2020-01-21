<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * This provider is injected in console context by the main provider or by the RestifyInjector
 * if a restify request.
 */
class RestifyCustomRoutesProvider extends ServiceProvider
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
//                $config = array_merge($config, $parameters[1]->getDefaultValue());
            }

            Route::group([], function ($router) use ($repository, $config) {
                if ($repository === 'Binaryk\LaravelRestify\Tests\RepositoryWithRoutes') {
                }
                $repository::routes($router, $config);
            });
        });
    }
}
