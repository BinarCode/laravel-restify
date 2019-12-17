<?php

Route::get('email/verify/{id}/{hash}', function($request) {
    //Silence is golden
})
    ->name('register.verify')->middleware([
    'signed',
    'throttle:6,1',
]);

Route::post('password/reset', function () {
    // Validate token
})->name('password.reset');
