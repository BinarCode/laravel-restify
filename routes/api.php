<?php

use Binaryk\LaravelRestify\Http\Controllers\RepositoryDestroyController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryShowController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryStoreController;
use Binaryk\LaravelRestify\Http\Controllers\RepositoryUpdateController;

Route::get('/{repository}', '\\' . RepositoryIndexController::class);
Route::post('/{repository}', '\\' . RepositoryStoreController::class);
Route::get('/{repository}/{repositoryId}', '\\' . RepositoryShowController::class);
Route::patch('/{repository}/{repositoryId}', '\\' . RepositoryUpdateController::class);
Route::put('/{repository}/{repositoryId}', '\\' . RepositoryUpdateController::class);
Route::delete('/{repository}/{repositoryId}', '\\' . RepositoryDestroyController::class);
