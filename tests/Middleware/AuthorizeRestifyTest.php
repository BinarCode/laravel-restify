<?php

namespace Binaryk\LaravelRestify\Tests\Middleware;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class AuthorizeRestifyTest extends IntegrationTestCase
{
    public function test_unauthorized_http_code_is_401(): void
    {
        Restify::$authUsing = fn () => false;

        $this->getJson(UserRepository::route())
            ->assertStatus(401);

        Restify::$authUsing = fn () => true;

        $this->getJson(UserRepository::route())
            ->assertStatus(200);
    }
}
