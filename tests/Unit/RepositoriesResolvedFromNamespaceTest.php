<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\WithRepositoriesDataProvider;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RepositoriesResolvedFromNamespaceTest extends IntegrationTestCase
{
    use WithRepositoriesDataProvider;

    protected function setUp(): void
    {
        TestCase::setUp();

        Restify::$repositories = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_repository_can_be_resolved_from_app_namespace(): void
    {
        Restify::repositoriesFrom(
            directory: realpath(__DIR__.'/../Fixtures/App/Restify'),
            namespace: 'App\\Restify\\',
        );

        $this->assertEquals(
            expected: 'App\\Restify\\UserRepository',
            actual: Restify::repositoryForModel('App\\User'),
        );

        $this->assertContains(
            'App\\Restify\\UserRepository',
            Restify::$repositories,
        );

        $this->assertInstanceOf(
            expected: 'App\\Restify\\UserRepository',
            actual: Restify::repository('users'),
        );
    }

    #[DataProvider('repositoryPathsFromFixtures')]
    public function test_repository_can_be_resolved_from_any_namespace(string $directory, string $namespace, string $serviceProvider): void
    {
        Restify::repositoriesFrom(
            directory: $directory,
            namespace: $namespace,
        );

        $this->assertCount(
            expectedCount: count(glob($directory.'/*Repository.php')),
            haystack: Restify::$repositories,
        );

        foreach (Restify::$repositories as $repository) {
            $this->assertInstanceOf(
                expected: $repository,
                actual: Restify::repository($repository::uriKey()),
            );
        }
    }
}
