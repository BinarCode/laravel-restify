<?php

Route::get('/{resource}', 'ResourceIndexController@handle');

Route::post('login', function () {
    // AuthService->login
});
Route::post('register', function () {
    // AuthService -> register
});
Route::get('email/verify/{id}/{hash}', function () {
    // AuthService -> verify
})->name('register.verify')->middleware([
    'signed',
    'throttle:6,1',
]);
Route::post('password/email', function () {
    // AuthService -> sendResetPasswordLinkEmail
});
Route::post('password/reset', function () {
    // AuthPassport -> resetPassword
})->name('password.reset');

