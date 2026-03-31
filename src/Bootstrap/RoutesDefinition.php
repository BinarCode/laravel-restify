<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Http\Controllers\FieldDestroyController;
use Binaryk\LaravelRestify\Http\Controllers\GlobalSearchController;
use Binaryk\LaravelRestify\Http\Controllers\ListActionsController;
use Binaryk\LaravelRestify\Http\Controllers\ListGettersController;
use Binaryk\LaravelRestify\Http\Controllers\ListRepositoryActionsController;
use Binaryk\LaravelRestify\Http\Controllers\ListRepositoryGettersController;
use Binaryk\LaravelRestify\Http\Controllers\PerformActionController;
use Binaryk\LaravelRestify\Http\Controllers\PerformGetterController;
use Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController;
use Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryGetterController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileUpdateController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryApplyFiltersController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryAttachController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyBulkController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryDetachController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryFilterController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryPatchController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreBulkController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController;
use Binaryk\LaravelRestify\Http\Controllers\RepositorySyncController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateBulkController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController;
use Binaryk\LaravelRestify\Http\Controllers\RestifyJsSetupController;
use Illuminate\Support\Facades\Route;

class RoutesDefinition
{
    private array $excludedMiddleware = [];

    public function __invoke(?string $uriKey = null)
    {
        $prefix = $uriKey ?: '{repository}';

        // Filters
        Route::get(
            $prefix.'/filters',
            RepositoryFilterController::class
        )->name('filters.index');

        Route::post(
            $prefix.'/apply-restify-advanced-filters',
            RepositoryApplyFiltersController::class
        )->name('filters.apply');

        // Actions
        Route::get(
            $prefix.'/actions',
            ListActionsController::class
        )->name('actions.index');
        Route::get(
            $prefix.'/{repositoryId}/actions',
            ListRepositoryActionsController::class
        )->name('actions.repository.index');
        Route::post(
            $prefix.'/action',
            PerformActionController::class
        )->name('actions.perform');
        Route::post(
            $prefix.'/actions',
            PerformActionController::class
        )->name('actions.performs'); // alias to the previous route
        Route::post(
            $prefix.'/{repositoryId}/action',
            PerformRepositoryActionController::class
        )->name('actions.repository.perform');
        Route::post(
            $prefix.'/{repositoryId}/actions',
            PerformRepositoryActionController::class
        )->name('actions.repository.performs'); // alias to the previous route

        // Getters
        Route::get(
            $prefix.'/getters',
            ListGettersController::class
        )->name('getters.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters',
            ListRepositoryGettersController::class
        )->name('getters.repository.index')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/getters/{getter}',
            PerformGetterController::class
        )->name('getters.perform')->withoutMiddleware($this->excludedMiddleware);
        Route::get(
            $prefix.'/{repositoryId}/getters/{getter}',
            PerformRepositoryGetterController::class
        )->name('getters.repository.perform')->withoutMiddleware($this->excludedMiddleware);

        // API CRUD
        Route::get(
            $prefix.'',
            RepositoryIndexController::class
        )->name('index')->withoutMiddleware($this->excludedMiddleware);
        Route::post(
            $prefix.'',
            RepositoryStoreController::class
        )->name('store');
        Route::post(
            $prefix.'/bulk',
            RepositoryStoreBulkController::class
        )->name('store.bulk');
        Route::post(
            $prefix.'/bulk/update',
            RepositoryUpdateBulkController::class
        )->name('update.bulk');
        Route::delete(
            $prefix.'/bulk/delete',
            RepositoryDestroyBulkController::class
        )->name('destroy.bulk');
        Route::get(
            $prefix.'/{repositoryId}',
            RepositoryShowController::class
        )->name('show')->withoutMiddleware($this->excludedMiddleware);
        Route::patch(
            $prefix.'/{repositoryId}',
            RepositoryPatchController::class
        )->name('patch');
        Route::put(
            $prefix.'/{repositoryId}',
            RepositoryUpdateController::class
        )->name('put');
        Route::post(
            $prefix.'/{repositoryId}',
            RepositoryUpdateController::class
        )->name('update');
        Route::delete(
            $prefix.'/{repositoryId}',
            RepositoryDestroyController::class
        )->name('destroy');

        if ($uriKey) {
            return;
        }

        // Fields
        Route::delete(
            $prefix.'/{repositoryId}/field/{field}',
            FieldDestroyController::class
        )->name('field.destroy');

        // Attach related repository id
        Route::post(
            $prefix.'/{repositoryId}/attach/{relatedRepository}',
            RepositoryAttachController::class
        )->name('attach');
        Route::post(
            $prefix.'/{repositoryId}/detach/{relatedRepository}',
            RepositoryDetachController::class
        )->name('detach');
        Route::post(
            $prefix.'/{repositoryId}/sync/{relatedRepository}',
            RepositorySyncController::class
        )->name('sync');

        // Relatable
        Route::get(
            '/{parentRepository}/{parentRepositoryId}/{repository}',
            RepositoryIndexController::class
        )->name('relatable.index');
        Route::post(
            '/{parentRepository}/{parentRepositoryId}/{repository}',
            RepositoryStoreController::class
        )->name('relatable.store');
        Route::get(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            RepositoryShowController::class
        )->name('relatable.show');
        Route::post(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            RepositoryUpdateController::class
        )->name('relatable.update');
        Route::put(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            RepositoryUpdateController::class
        )->name('relatable.updatePut');
        Route::delete(
            '/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            RepositoryDestroyController::class
        )->name('relatable.destroy');
    }

    public function once(): void
    {
        Route::get('/search', GlobalSearchController::class)->name('search');

        Route::get('/profile', ProfileController::class)->name('profile');
        Route::put('/profile', ProfileUpdateController::class)->name('profile.updatePut');
        Route::post('/profile', ProfileUpdateController::class)->name('profile.updatePost');

        // RestifyJS
        Route::get('/restifyjs/setup',
            RestifyJsSetupController::class)->withoutMiddleware('auth:sanctum')->name('restifyjs.setup');
    }

    public function withoutMiddleware(...$middleware): self
    {
        $this->excludedMiddleware = $middleware;

        return $this;
    }
}
