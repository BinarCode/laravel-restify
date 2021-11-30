<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class RoutesBoot
{
    public function __construct(
        private Application $app,
    ) {
    }

    public function boot(): void
    {
        $config = [
            'namespace' => null,
            'as' => 'restify.api.',
            'prefix' => Restify::path(),
            'middleware' => config('restify.middleware', []),
        ];

        $this->defaultRoutes($config)
            ->crudRoutes($config)
            ->registerPrefixed($config)
            ->registerIndexPrefixed($config);
    }

    public function defaultRoutes($config): self
    {
        Route::group($config, function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });

        return $this;
    }

    public function crudRoutes($config): self
    {
        Restify::collectRepositories()->each(function (string $repository) use ($config) {
            dd($repository);
            /** @var string|Repository $repository */
//            $config['prefix'] = $repository::uriKey();

            Route::group($config, function (Router $router) use ($repository) {
                $router->get($repository::to(), RepositoryIndexController::class);
            });
        });

        return $this;
    }

    public function registerPrefixed($config): self
    {
        collect(Restify::$repositories)
            ->filter(fn ($repository) => $repository::prefix())
            ->each(function (string $repository) use ($config) {
                /** @var string|Repository $repository */
                $config['prefix'] = $repository::prefix();
                Route::group($config, function () {
                    $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
                });
            });

        return $this;
    }

    public function registerIndexPrefixed($config): self
    {
        collect(Restify::$repositories)
            ->filter(fn ($repository) => $repository::hasIndexPrefix())
            ->each(function ($repository) use ($config) {
                $config['prefix'] = $repository::indexPrefix();
                Route::group($config, function () {
                    Route::get('/{repository}', '\\'.RepositoryIndexController::class);
                });
            });

        return $this;
    }

    private function loadRoutesFrom(string $path): self
    {
        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            require $path;
        }

        return $this;
    }
}
