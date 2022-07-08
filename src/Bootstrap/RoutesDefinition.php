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
    public function __invoke(string $uriKey = null)
    {
        $prefix = $uriKey ?: '{repository}';

        // Filters
        Route::get($prefix.'/filters',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryFilterController::class);

        // Actions
        Route::get($prefix.'/actions',
            \Binaryk\LaravelRestify\Http\Controllers\ListActionsController::class)->name('restify.actions.index');
        Route::get($prefix.'/{repositoryId}/actions',
            \Binaryk\LaravelRestify\Http\Controllers\ListRepositoryActionsController::class)->name('restify.actions.repository.index');
        Route::post($prefix.'/action',
            \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class)->name('restify.actions.perform');
        Route::post($prefix.'/actions',
            \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class); // alias to the previous route
        Route::post($prefix.'/{repositoryId}/action',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class)->name('restify.actions.repository.perform');
        Route::post($prefix.'/{repositoryId}/actions',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class); // alias to the previous route

        // Getters
        Route::get($prefix.'/getters',
            \Binaryk\LaravelRestify\Http\Controllers\ListGettersController::class)->name('restify.getters.index');
        Route::get($prefix.'/{repositoryId}/getters',
            \Binaryk\LaravelRestify\Http\Controllers\ListRepositoryGettersController::class)->name('restify.getters.repository.index');
        Route::get($prefix.'/getters/{getter}',
            \Binaryk\LaravelRestify\Http\Controllers\PerformGetterController::class)->name('restify.getters.perform');
        Route::get($prefix.'/{repositoryId}/getters/{getter}',
            \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryGetterController::class)->name('restify.getters.repository.perform');

        // API CRUD
        Route::get($prefix.'',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class)->name('index');
        Route::post($prefix.'',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class)->name('restify.store');
        Route::post($prefix.'/bulk',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreBulkController::class)->name('restify.store.bulk');
        Route::post($prefix.'/bulk/update',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateBulkController::class)->name('restify.update.bulk');
        Route::delete($prefix.'/bulk/delete',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyBulkController::class)->name('restify.destroy.bulk');
        Route::get($prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class)->name('restify.show');
        Route::patch($prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryPatchController::class)->name('restify.patch');
        Route::put($prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class)->name('restify.put');
        Route::post($prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class)->name('restify.update');
        Route::delete($prefix.'/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class)->name('restify.destroy');

        if ($uriKey) {
            return;
        }

        // Fields
        Route::delete($prefix.'/{repositoryId}/field/{field}',
            \Binaryk\LaravelRestify\Http\Controllers\FieldDestroyController::class);

        // Attach related repository id
        Route::post($prefix.'/{repositoryId}/attach/{relatedRepository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryAttachController::class);
        Route::post($prefix.'/{repositoryId}/detach/{relatedRepository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDetachController::class);

        // Relatable
        Route::get('/{parentRepository}/{parentRepositoryId}/{repository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class);
        Route::post('/{parentRepository}/{parentRepositoryId}/{repository}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class);
        Route::get('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class);
        Route::post('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
        Route::put('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
        Route::delete('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}',
            \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class);
    }

    public function once(): void
    {
        Route::get('/search', GlobalSearchController::class);

        Route::get('/profile', ProfileController::class);
        Route::put('/profile', ProfileUpdateController::class);
        Route::post('/profile', ProfileUpdateController::class);

        // RestifyJS
        Route::get('/restifyjs/setup', RestifyJsSetupController::class)->withoutMiddleware(
            RestifySanctumAuthenticate::class,
        );
    }
}
