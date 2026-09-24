<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions;

use Binaryk\LaravelRestify\Http\Controllers\RepositoryIndexController;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\SampleUser;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositoryExceptionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Restify::repositories([
            RepositoryWithoutAGatePolicyForRepositoryExceptionTest::class,
        ]);
    }

    protected function tearDown(): void
    {
        PostRepository::setPrefix(null);

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        $router->get(
            '/a-route-with-no-repository-key',
            RepositoryIndexController::class,
        );
    }

    #[Test]
    public function a_request_that_cannot_resolve_a_repository_key_is_rejected(): void
    {
        $this->getJson('/a-route-with-no-repository-key')
            ->assertStatus(400)
            ->assertJsonPath('message', 'Repository key missing.');
    }

    #[Test]
    public function a_repository_whose_model_has_no_gate_policy_is_unauthorized(): void
    {
        $uriKey = RepositoryWithoutAGatePolicyForRepositoryExceptionTest::uriKey();

        $this->getJson(Restify::path($uriKey))
            ->assertStatus(403)
            ->assertJsonPath('message', "Unauthorized to view repository {$uriKey}. Check \"allowRestify\" policy.");
    }

    #[Test]
    public function a_repository_route_outside_its_declared_prefix_is_unauthorized(): void
    {
        PostRepository::setPrefix('api/v1');

        $this->getJson(Restify::path(PostRepository::uriKey()))
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Unauthorized to use the route '.Restify::path(PostRepository::uriKey()).'. Check prefix.',
            );
    }
}

class RepositoryWithoutAGatePolicyForRepositoryExceptionTest extends Repository
{
    public static $model = SampleUser::class;
}
