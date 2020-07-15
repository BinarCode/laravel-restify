<?php

namespace Binaryk\LaravelRestify\Tests\Repository;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\EmptyMiddleware;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepositoryWithoutMiddleware;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryWithoutMiddlewareTest extends IntegrationTest
{
    protected function setUp(): void
    {
        Restify::repositories([
            UserRepositoryWithoutMiddleware::class,
        ]);

        parent::setUp();

        $this->app['config']->set('restify.middleware', [EmptyMiddleware::class]);
    }

    public function test_can_exclude_middlewares()
    {
        $this->getJson('restify-api/user-without-middleware')
        ->dump();

        $this->assertFalse(
            EmptyMiddleware::$called
        );
    }
}
