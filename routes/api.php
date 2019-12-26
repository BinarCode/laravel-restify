<?php

Route::get('/{repository}', 'RepositoryIndexController@handle');
Route::post('/{repository}', 'RepositoryStoreController@handle');
Route::get('/{repository}/{repositoryId}', 'RepositoryShowController@handle');
Route::put('/{repository}/{repositoryId}', 'RepositoryUpdateController@handle');
