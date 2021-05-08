<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostAbortMiddleware;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithCustomMiddlewareRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryMiddlewaresTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        PostRepository::$middleware = [];
    }

    public function test_repository_can_have_custom_middleware(): void
    {
        PostRepository::$middleware = [
            function () {
                abort(404);
            }
        ];

        $this->getJson(PostRepository::to())->assertNotFound();
    }

    public function test_foreign_repository_middleware_should_not_be_invoked(): void
    {
        $middleware = $this->mock(PostAbortMiddleware::class);

        $middleware
            ->expects('handle')
            ->never();

        PostWithCustomMiddlewareRepository::$middleware = [
            $middleware,
        ];

        Restify::repositories([
            PostWithCustomMiddlewareRepository::class,
        ]);

        $this->getJson(PostRepository::to())->assertOk();
    }
}
