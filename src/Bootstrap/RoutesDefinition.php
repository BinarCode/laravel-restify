<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Controllers\GlobalSearchController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileUpdateController;
use Binaryk\LaravelRestify\Http\Controllers\RestifyJsSetupController;
use Binaryk\LaravelRestify\Http\Middleware\RestifySanctumAuthenticate;
use Illuminate\Support\Facades\Route;

class RoutesDefinition
{
    private array $excludedMiddleware = [];

    public function __invoke(string $uriKey = null)
    {
        $prefix = $uriKey ?: '{repository}';

        // Filters
        Route::get(
            $prefix.'/filters',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryFilterController::class
        )->name('filters.index');

        // Actions
        Route::get(
            $prefix.'/actions',
            \Binaryk\LaravelRestify\Http\Controllers\ListActionsController::class
        )->name('actions.index');
        Route::get(
            $prefix.'/{repositoryId}/actions',
            \Binaryk\LaravelRestify\Http\Controllers\ListRepositoryActionsController::class
        )->name('actions.repository.index');
        Route::post(
            $prefix.'/action',
            \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class
        )->name('actions.perform');
        Route::post(
            $prefix.'/actions',
            \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class
        )->name('actions.performs'); // alias to the previous route
        Route::post(
            $prefix.'/{repositoryId}/action',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class
        )->name('actions.repository.perform');
        Route::post(
            $prefix.'/{repositoryId}/actions',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class
        )->name('actions.repository.performs'); // alias to the previous route

        // Getters
        Route::get(
            $prefix.'/getters',
            \Binaryk\LaravelRestify\Http\Controllers\ListGettersController::class
        )->name('getters.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters',
            \Binaryk\LaravelRestify\Http\Controllers\ListRepositoryGettersController::class
        )->name('getters.repository.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/getters/{getter}',
            \Binaryk\LaravelRestify\Http\Controllers\PerformGetterController::class
        )->name('getters.perform')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters/{getter}',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryGetterController::class
        )->name('getters.repository.perform')->withoutMiddleware($this->excludedMiddleware);

        // API CRUD
        Route::get(
            $prefix.'',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class
        )->name('index')->withoutMiddleware($this->excludedMiddleware);
        Route::post(
            $prefix.'',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class
        )->name('store');
        Route::post(
            $prefix.'/bulk',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreBulkController::class
        )->name('store.bulk');
        Route::post(
            $prefix.'/bulk/update',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateBulkController::class
        )->name('update.bulk');
        Route::delete(
            $prefix.'/bulk/delete',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyBulkController::class
        )->name('destroy.bulk');
        Route::get(
            $prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class
        )->name('show')->withoutMiddleware($this->excludedMiddleware);
        Route::patch(
            $prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryPatchController::class
        )->name('patch');
        Route::put(
            $prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class
        )->name('put');
        Route::post(
            $prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class
        )->name('update');
        Route::delete(
            $prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class
        )->name('destroy');

        if ($uriKey) {
            return;
        }

        // Fields
        Route::delete(
            $prefix.'/{repositoryId}/field/{field}',
            \Binaryk\LaravelRestify\Http\Controllers\FieldDestroyController::class
        )->name('field.destroy');

        // Attach related repository id
        Route::post(
            $prefix.'/{repositoryId}/attach/{relatedRepository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryAttachController::class
        )->name('attach');
        Route::post(
            $prefix.'/{repositoryId}/detach/{relatedRepository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDetachController::class
        )->name('detach');
        Route::post(
            $prefix.'/{repositoryId}/sync/{relatedRepository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositorySyncController::class
        )->name('sync');

        // Relatable
        Route::get(
            '/{parentRepository}/{parentRepositoryId}/{repository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class
        )->name('relatable.index');
        Route::post(
            '/{parentRepository}/{parentRepositoryId}/{repository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class
        )->name('relatable.store');
        Route::get(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class
        )->name('relatable.show');
        Route::post(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class
        )->name('relatable.update');
        Route::put(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class
        )->name('relatable.updatePut');
        Route::delete(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class
        )->name('relatable.destroy');
    }

    public function once(): void
    {
        Route::get('/search', GlobalSearchController::class)->name('search');

        Route::get('/profile', ProfileController::class)->name('profile');
        Route::put('/profile', ProfileUpdateController::class)->name('profile.updatePut');
        Route::post('/profile', ProfileUpdateController::class)->name('profile.updatePost');

        // RestifyJS
        Route::get('/restifyjs/setup', RestifyJsSetupController::class)->withoutMiddleware(
            RestifySanctumAuthenticate::class,
        )->name('restifyjs.setup');
    }

    public function withoutMiddleware(...$middleware): self
    {
        $this->excludedMiddleware = $middleware;

        return $this;
    }
}
