<?php

namespace Binaryk\LaravelRestify\Tests\Repositories;

use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class PublicRepositoriesTest extends IntegrationTest
{
    protected function setUp(): void
    {
        UserRepository::$public = true;

        parent::setUp();

        config()->set('restify.middleware', [
            'auth:sanctum' => function ($request, $next) {
                abort(403);
            }
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        UserRepository::$public = false;
    }

    public function test_cannot_access_public_repository(): void
    {
        $this->logout();

        $this->getJson(UserRepository::route())->assertForbidden();
    }
}
