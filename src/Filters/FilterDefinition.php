<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use JsonSerializable;
use ReturnTypeWillChange;

abstract class FilterDefinition implements JsonSerializable
{
    public static string $type = 'string';

    public static string $relatedRepositoryKey;

    public function getType(): string
    {
        return static::$type;
    }

    public function getRelatedRepositoryKey(): ?string
    {
        return static::$relatedRepositoryKey;
    }

    public function getRelatedRepositoryUrl(): ?string
    {
        return ($key = $this->getRelatedRepositoryKey())
            ? with(Restify::repositoryClassForKey($key), function ($repository = null) {
                if (is_subclass_of($repository, Repository::class)) {
                    return Restify::path($repository::uriKey());
                }
            })
            : null;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return with([
            'type' => $this->getType(),
        ], function (array $initial) {
            return static::$relatedRepositoryKey
                ? array_merge($initial, [
                    'related_repository_key' => $this->getRelatedRepositoryKey(),
                    'related_repository_url' => $this->getRelatedRepositoryUrl(),
                ])
                : $initial;
        });
    }
}
