<?php

Route::get('/{repository}', 'RepositoryIndexController@handle');
Route::post('/{repository}', 'RepositoryStoreController@handle');
