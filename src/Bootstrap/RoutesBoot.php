<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Support\Facades\Route;

class RoutesBoot
{
    public function boot(): void
    {
        $config = [
            'namespace' => null,
            'as' => 'restify.api.',
            'prefix' => Restify::path(),
            'middleware' => config('restify.middleware', []),
        ];

        $this
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
        require $path;

        return $this;
    }
}
