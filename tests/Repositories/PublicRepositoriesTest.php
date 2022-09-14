<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class PublicRepositoriesTest extends IntegrationTest
{
    protected function setUp(): void
    {
        UserRepository::$public = true;
//        Restify::repositories([
//            UserRepository::class,
//        ]);

        parent::setUp();

        Restify::auth(function () {
            return false;
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        UserRepository::$public = false;
    }

    public function test_can_access_public_repository(): void
    {
        $this->logout();

        $this->getJson(UserRepository::route())->assertOk();
    }

    public function test_cannot_modify_public_repository(): void
    {
        $this->logout();

        $this->postJson(UserRepository::route())->assertUnauthorized();
    }
}
