<?php

namespace Binaryk\LaravelRestify\Cache;

class RestifyCache
{
    public static function enabled(string $for): bool
    {
        return config("restify.cache.{$for}.enabled", false);
    }
}
