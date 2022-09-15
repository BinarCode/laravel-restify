<?php

namespace Binaryk\LaravelRestify\Cache;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PolicyCache
{
    public static function enabled(): bool
    {
        return config('restify.cache.policies.enabled', false);
    }

    public static function keyForAllowRestify(string $repositoryKey): string
    {
        $user = app(Request::class)->user();

        return "restify.policy.allowRestify.repository-$repositoryKey.user-".$user?->getKey();
    }

    public static function keyForPolicyMethods(string $repositoryKey, string $policyMethod, string|int|null $modelKey): string
    {
        $modelKey = $modelKey ?? Str::random();

        $user = app(Request::class)->user();

        return "restify.policy.$policyMethod.repository-$repositoryKey.resource-$modelKey.user-".$user?->getKey();
    }

    public static function resolve(string $key, callable|Closure $data): mixed
    {
        if (! static::enabled()) {
            return $data();
        }

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        Cache::put($key, $data = $data(), config('restify.cache.policies.ttl', 60));

        return $data;
    }
}
