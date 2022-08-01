<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorizeRestifyTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_unauthorized_http_code_is_401(): void
    {
        $this->logout();

        Restify::$authUsing = fn () => false;

        $this->getJson(UserRepository::route())
            ->assertStatus(401);
    }
}
