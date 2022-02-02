<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Controllers\PerformGetterController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Application;
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

        $this
//            ->registerCustomGettersPerforms($config)
            ->defaultRoutes($config)
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

    public function registerPrefixed($config): self
    {
        collect(Restify::$repositories)
            ->filter(fn ($repository) => $repository::prefix())
            ->each(function (string $repository) use ($config) {
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

    // @deprecated
    public function registerCustomGettersPerforms($config): self
    {
        collect(Restify::$repositories)
            ->filter(function ($repository) use ($config) {
                return collect(app($repository)
                    ->getters(app(RestifyRequest::class)))
                    ->each(function (Getter $getter) use ($config, $repository) {
                        if (count($excludedMiddleware = $getter->excludedMiddleware())) {
                            Route::group($config, function () use ($excludedMiddleware, $repository, $getter) {
                                $getterKey = $getter->uriKey();

                                Route::get("/{repository}/getters/$getterKey", PerformGetterController::class)
                                    ->withoutMiddleware($excludedMiddleware);
                            });
                        }
                    });
            });

        return $this;
    }
}
