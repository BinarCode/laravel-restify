<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Support\Facades\Route;

class RoutesBoot
{
    public function boot(): void
    {
        Restify::ensureRepositoriesLoaded();

        $config = [
            'namespace' => null,
            'as' => 'restify.api.',
            'prefix' => Restify::path(),
            'middleware' => config('restify.middleware', []),
        ];

        $this
            ->registerPrefixed($config)
            ->registerPublic($config)
            ->defaultRoutes($config);
    }

    public function defaultRoutes($config): self
    {
        Route::group($config, function () {
            app(RoutesDefinition::class)->once();
            app(RoutesDefinition::class)();
        });

        return $this;
    }

    public function registerPrefixed($config): self
    {
        collect(Restify::$repositories)
            /** * @var Repository $repository */
            ->each(function (string $repository) use ($config) {
                if (! $repository::prefix()) {
                    return;
                }

                $config['prefix'] = $repository::prefix();
                Route::group($config, function () use ($repository) {
                    app(RoutesDefinition::class)($repository::uriKey());
                });
            });

        return $this;
    }

    public function registerPublic($config): self
    {
        collect(Restify::$repositories)
            ->each(function (string $repository) use ($config) {
                /**
                 * @var Repository $repository
                 */
                if (! $repository::isPublic()) {
                    return;
                }

                Route::group($config, function () use ($repository) {
                    app(RoutesDefinition::class)->withoutMiddleware('auth:sanctum')($repository::uriKey());
                });
            });

        return $this;
    }
}
