<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Controllers\ListGettersController;
use Binaryk\LaravelRestify\Http\Controllers\ListRepositoryGettersController;
use Binaryk\LaravelRestify\Http\Controllers\PerformGetterController;
use Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryGetterController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController;
use Illuminate\Support\Facades\Route;

class PublicRoutesDefinition
{
    private array $excludedMiddleware = [];

    public function __invoke(string $uriKey = null)
    {
        $prefix = $uriKey ?: '{repository}';

        // Getters
        Route::get(
            $prefix.'/getters',
            ListGettersController::class
        )->name('restify.getters.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters',
            ListRepositoryGettersController::class
        )->name('restify.getters.repository.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/getters/{getter}',
            PerformGetterController::class
        )->name('restify.getters.perform')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters/{getter}',
            PerformRepositoryGetterController::class
        )->name('restify.getters.repository.perform')->withoutMiddleware($this->excludedMiddleware);

        Route::get(
            $prefix,
            RepositoryIndexController::class
        )->name('index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}',
            RepositoryShowController::class
        )->name('restify.show')->withoutMiddleware($this->excludedMiddleware);
    }

    public function withoutMiddleware(...$middleware): self
    {
        $this->excludedMiddleware = $middleware;

        return $this;
    }
}
