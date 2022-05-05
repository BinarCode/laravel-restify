<?php

namespace Binaryk\LaravelRestify\Exceptions;

use Exception;

final class RepositoryException extends Exception
{
    public static function missingKey(string $class = 'Repository key missing.'): self
    {
        return new self($class);
    }

    public static function unauthorized(string $class = null): self
    {
        return new self(__('Unauthorized to view repository :name. Check "allowRestify" policy.', [
            'name' => $class,
        ]), code: 403);
    }

    public static function routeUnauthorized(string $uri = null): self
    {
        return new self(__('Unauthorized to use the route :name. Check prefix.', [
            'name' => $uri,
        ]), code: 403);
    }
}
