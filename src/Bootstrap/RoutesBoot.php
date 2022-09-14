<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Support\Facades\Route;

class RoutesBoot
{
    public function boot(): void
    {
        Restify::ensureRepositoriesLoaded();

        $this
            ->registerPrefixed()
            ->defaultRoutes();
    }

    public function defaultRoutes(): self
    {
        Route::group($this->routesBaseConfig(), function () {
            app(RoutesDefinition::class)->once();
            dd(Restify::$repositories);
            app(RoutesDefinition::class)();
        });

        return $this;
    }

    public function registerPrefixed(): self
    {
        collect(Restify::$repositories)
            /** * @var Repository $repository */
            ->each(function (string $repository) {
                if (!$repository::prefix()) {
                    return;
                }

                $config = $this->routesBaseConfig();

                $config['prefix'] = $repository::prefix();
                Route::group($config, function () use ($repository) {
                    app(RoutesDefinition::class)($repository::uriKey());
                });
            });

        return $this;
    }

    public function routesBaseConfig(): array
    {
        return [
            'namespace' => null,
            'as' => 'restify.api.',
            'prefix' => Restify::path(),
            'middleware' => config('restify.middleware', []),
        ];
    }
}
