<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class LoginControllerTest extends IntegrationTest
{
    public function test_user_can_successfully_login()
    {
       $this->markTestSkipped();

        Route::restifyAuth();

        $user = User::create([
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'password' => Hash::make('secret!'),
        ]);

        $this->postJson(route('restify.login', [
            'email' => $user->email,
            'password' => 'secret!',
        ]))->assertOk()->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'updated_at',
                ],
                'token',
            ],
        ]);
    }

    public function test_user_cant_login_with_invalid_credentiales()
    {
        Route::restifyAuth();

        $user = User::create([
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'password' => Hash::make('secret!'),
        ]);

        $this->postJson(route('restify.login', [
            'email' => $user->email,
            'password' => 'secret!!',
        ]))->assertUnauthorized();
    }
}
