<?php

namespace Binaryk\LaravelRestify\Tests\Concerns;

trait WithRepositoriesDataProvider
{
    public static function repositoryPathsFromFixtures(): array
    {
        return [
            [
                'directory' => realpath(__DIR__.'/../Fixtures/CustomNamespace/PackageA/Restify'),
                'namespace' => 'CustomNamespace\\PackageA\\Restify\\',
                'serviceProvider' => 'CustomNamespace\\PackageA\\PackageAServiceProvider',
            ],
            [
                'directory' => realpath(__DIR__.'/../Fixtures/CustomNamespace/PackageB/Restify'),
                'namespace' => 'CustomNamespace\\PackageB\\Restify\\',
                'serviceProvider' => 'CustomNamespace\\PackageB\\PackageBServiceProvider',
            ],
            [
                'directory' => realpath(__DIR__.'/../Fixtures/CustomNamespace/PackageC/Restify'),
                'namespace' => 'CustomNamespace\\PackageC\\Restify\\',
                'serviceProvider' => 'CustomNamespace\\PackageC\\PackageCServiceProvider',
            ],
        ];
    }
}
