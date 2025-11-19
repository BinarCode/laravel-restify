<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Restify;
use ReflectionException;

trait InteractsWithRestifyRepositories
{
    /**
     * Register the application's Rest resources.
     *
     * @throws ReflectionException
     */
    protected function loadRestifyFrom(string $directory, string $namespace): void
    {
        Restify::repositoriesFrom(
            directory: $directory,
            namespace: $namespace,
        );
    }
}
