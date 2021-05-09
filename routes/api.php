<?php

// Global Search...
Route::get('/search', \Binaryk\LaravelRestify\Http\Controllers\GlobalSearchController::class);

Route::get('/profile', \Binaryk\LaravelRestify\Http\Controllers\ProfileController::class);
Route::put('/profile', \Binaryk\LaravelRestify\Http\Controllers\ProfileUpdateController::class);
Route::post('/profile', \Binaryk\LaravelRestify\Http\Controllers\ProfileUpdateController::class);
Route::post('/profile/avatar', \Binaryk\LaravelRestify\Http\Controllers\ProfileAvatarController::class);

// RestifyJS
Route::get('/restifyjs/setup', \Binaryk\LaravelRestify\Http\Controllers\RestifyJsSetupController::class)->withoutMiddleware(
    Binaryk\LaravelRestify\Http\Middleware\RestifySanctumAuthenticate::class,
);

// Filters
Route::get('/{repository}/filters', \Binaryk\LaravelRestify\Http\Controllers\RepositoryFilterController::class);

// Actions
Route::get('/{repository}/actions', \Binaryk\LaravelRestify\Http\Controllers\ListActionsController::class);
Route::get('/{repository}/{repositoryId}/actions', \Binaryk\LaravelRestify\Http\Controllers\ListRepositoryActionsController::class);
Route::post('/{repository}/action', \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class);
Route::post('/{repository}/actions', \Binaryk\LaravelRestify\Http\Controllers\PerformActionController::class); // alias to the previous route
Route::post('/{repository}/{repositoryId}/action', \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class);
Route::post('/{repository}/{repositoryId}/actions', \Binaryk\LaravelRestify\Http\Controllers\PerformRepositoryActionController::class); // alias to the previous route

// API CRUD
Route::get('/{repository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class);
Route::post('/{repository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class);
Route::post('/{repository}/bulk', \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreBulkController::class);
Route::post('/{repository}/bulk/update', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateBulkController::class);
Route::get('/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class);
Route::patch('/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
Route::put('/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
Route::post('/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
Route::delete('/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class);

// Fields
Route::delete('/{repository}/{repositoryId}/field/{field}', \Binaryk\LaravelRestify\Http\Controllers\FieldDestroyController::class);

// Attach related repository id
Route::post('/{repository}/{repositoryId}/attach/{relatedRepository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryAttachController::class);
Route::post('/{repository}/{repositoryId}/detach/{relatedRepository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryDetachController::class);

// Relatable
Route::get('/{parentRepository}/{parentRepositoryId}/{repository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController::class);
Route::post('/{parentRepository}/{parentRepositoryId}/{repository}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController::class);
Route::get('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController::class);
Route::post('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
Route::put('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController::class);
Route::delete('/{parentRepository}/{parentRepositoryId}/{repository}/{repositoryId}', \Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController::class);
