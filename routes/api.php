<?php

Route::get('/{repository}', 'RepositoryIndexController@handle');
Route::post('/{repository}', 'RepositoryStoreController@handle');
Route::get('/{repository}/{repositoryId}', 'RepositoryShowController@handle');
Route::patch('/{repository}/{repositoryId}', 'RepositoryUpdateController@handle');
Route::put('/{repository}/{repositoryId}', 'RepositoryUpdateController@handle');
Route::delete('/{repository}/{repositoryId}', 'RepositoryDestroyController@handle');
