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
    public function boot()
    {
        $this->registerRoutes();
    }

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

            $wrap = [];

            if (count($parameters) >= 2 && $parameters[1] instanceof \ReflectionParameter) {
                $default = $parameters[1]->isDefaultValueAvailable() ? $parameters[1]->getDefaultValue() : [];
                $config = array_merge($config, $default);
            }

            if (count($parameters) === 3) {
                $wrap = ($parameters[2]->isDefaultValueAvailable() && $parameters[2]->getDefaultValue()) ? $config : [];
            }

            Route::group($wrap, function ($router) use ($repository, $config) {
                $repository::routes($router, $config);
            });
        });
    }
}
