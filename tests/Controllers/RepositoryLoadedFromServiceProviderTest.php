<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\WithRepositoriesDataProvider;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RepositoryLoadedFromServiceProviderTest extends IntegrationTestCase
{
    use WithRepositoriesDataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        Restify::$repositories = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[DataProvider('repositoryPathsFromFixtures')]
    public function test_repositories_can_be_loaded_with_service_provider_register_method(
        string $directory,
        string $namespace,
        ?string $serviceProvider = null,
    ): void {
        if (! $serviceProvider) {
            $this->markTestSkipped('No service provider was found in directory '.$directory.' skipping this iteration.');
        }

        $this->app->register($serviceProvider);

        foreach (Restify::$repositories as $repository) {
            $route = $repository::route();
            $this->getJson($route)->assertOk();
        }

        // Clears repositories so it does not affect other tests.
        Restify::$repositories = [];
    }
}
