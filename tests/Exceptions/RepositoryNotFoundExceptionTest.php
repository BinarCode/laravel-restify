<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Exceptions;

use Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositoryNotFoundExceptionTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        config(['app.debug' => false]);

        parent::tearDown();
    }

    #[Test]
    public function requesting_an_unregistered_repository_key_throws_the_real_exception(): void
    {
        config(['app.debug' => true]);

        $this->getJson(Restify::path('a-repository-that-does-not-exist'))
            ->assertStatus(500)
            ->assertJson([
                'exception' => RepositoryNotFoundException::class,
                'message' => 'Repository a-repository-that-does-not-exist not found.',
            ]);
    }
}
