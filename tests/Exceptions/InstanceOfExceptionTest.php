<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions;

use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class InstanceOfExceptionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);

        Restify::repositories([
            RepositoryWithoutAModelForInstanceOfExceptionTest::class,
        ]);
    }

    #[Test]
    public function indexing_a_repository_without_a_model_throws_the_real_exception(): void
    {
        $this->getJson(Restify::path(RepositoryWithoutAModelForInstanceOfExceptionTest::uriKey()))
            ->assertInternalServerError()
            ->assertJson([
                'exception' => InstanceOfException::class,
                'message' => 'Model is not defined in the repository.',
            ]);
    }
}

class RepositoryWithoutAModelForInstanceOfExceptionTest extends Repository
{
    public static function authorizedToUseRepository(Request $request): bool
    {
        return true;
    }

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}
