<?php

use Binaryk\LaravelRestify\Http\Controllers\GlobalSearchController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileAvatarController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileController;
use Binaryk\LaravelRestify\Http\Controllers\ProfileUpdateController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryAttachController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryDetachController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryFilterController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController;
use Illuminate\Support\Facades\Route;

// Global Search...
Route::get('/search', '\\'.GlobalSearchController::class);

Route::get('/profile', '\\' . ProfileController::class);
Route::put('/profile', '\\' . ProfileUpdateController::class);
Route::post('/profile/avatar', '\\' . ProfileAvatarController::class);

// Filters
Route::get('/{repository}/filters', '\\'.RepositoryFilterController::class);

// API CRUD
Route::get('/{repository}', '\\'.RepositoryIndexController::class);
Route::post('/{repository}', '\\'.RepositoryStoreController::class);
Route::get('/{repository}/{repositoryId}', '\\'.RepositoryShowController::class);
Route::patch('/{repository}/{repositoryId}', '\\'.RepositoryUpdateController::class);
Route::put('/{repository}/{repositoryId}', '\\'.RepositoryUpdateController::class);
Route::post('/{repository}/{repositoryId}', '\\'.RepositoryUpdateController::class);
Route::delete('/{repository}/{repositoryId}', '\\'.RepositoryDestroyController::class);

// Attach related repository id
Route::post('/{repository}/{repositoryId}/attach/{relatedRepository}', '\\'.RepositoryAttachController::class);
Route::post('/{repository}/{repositoryId}/detach/{relatedRepository}', '\\'.RepositoryDetachController::class);
