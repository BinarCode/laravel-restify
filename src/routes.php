<?php

use Illuminate\Support\Facades\Route;

Route::get('email/verify/{id}/{hash}', 'AuthController@verify')->name('register.verify')->middleware([
    'signed',
    'throttle:6,1',
]);

Route::post('password/reset', function () {
    // Validate token
})->name('password.reset');
