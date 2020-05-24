<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostAbortMiddleware;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithCustomMiddlewareRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RepositoryMiddlewaresTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_repository_can_have_custom_middleware()
    {
        $middleware = m::mock(PostAbortMiddleware::class);

        $nextParam = null;

        $middleware
            ->expects('handle')
            ->once();

        PostWithCustomMiddlewareRepository::$middlewares = [
            $middleware,
        ];

        Restify::repositories([
            PostWithCustomMiddlewareRepository::class,
        ]);

        $this->getJson('restify-api/post-with-middleware')
            ->assertStatus(200);
    }

    public function test_request_fails_if_middleware_abort()
    {
        PostWithCustomMiddlewareRepository::$middlewares = [
            PostAbortMiddleware::class
        ];

        Restify::repositories([
            PostWithCustomMiddlewareRepository::class,
        ]);

        $this->getJson('restify-api/post-with-middleware')
            ->assertStatus(404);
    }

    public function test_foreign_repository_middleware_should_not_be_invoked()
    {

        $middleware = m::mock(PostAbortMiddleware::class);

        $nextParam = null;

        $middleware
            ->expects('handle')
            ->never();

        PostWithCustomMiddlewareRepository::$middlewares = [
            $middleware,
        ];

        Restify::repositories([
            PostWithCustomMiddlewareRepository::class,
        ]);

        $this->getJson('restify-api/posts')
            ->assertStatus(200);
    }
}
