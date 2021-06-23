<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Auth;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Route;

class VerifyControllerTest extends IntegrationTest
{
    public function test_user_can_verify_account()
    {
        $this->withoutExceptionHandling();

        Route::restifyAuth();

        $userRegistered = User::create([
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'password' => 'secret!',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->assertNull($userRegistered->email_verified_at);

       $verifiedUser = $this->postJson(route('restify.verify', [
            $userRegistered->id,
            sha1($userRegistered->email)
        ]))->assertOk()->json();

        $this->assertNotNull($verifiedUser['email_verified_at']);
    }

    public function test_user_cant_verify_account()
    {
        Route::restifyAuth();

        $userRegistered = User::create([
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'password' => 'secret!',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Vasile',
            'email' => 'vasile.papuc@gmail.com',
            'email_verified_at' => null,
        ]);

        $this->assertNull($userRegistered->email_verified_at);

        $this->postJson(route('restify.verify', [
            $userRegistered->id,
            sha1('exemple@exemple.com')
        ]))->assertForbidden();
    }
}
