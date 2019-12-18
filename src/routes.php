<?php

Route::post('login', function ($request) {
    // AuthService->login
});
Route::post('register', function ($request ) {
    // AuthService -> register
});
Route::get('email/verify/{id}/{hash}', function ($request) {
    // AuthService -> verify
})->name('register.verify')->middleware([
    'signed',
    'throttle:6,1'
]);
Route::post('password/email', function ($request) {
    // AuthService -> sendResetPasswordLinkEmail
});
Route::post('password/reset', function ($request) {
    // AuthPassport -> resetPassword
})->name('password.reset');
