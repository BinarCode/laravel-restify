<?php

namespace Binaryk\LaravelRestify\Cache;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use stdClass;

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

    public static function resolve(string $key, callable|Closure $data, Model $model): mixed
    {
        if (! static::enabled()) {
            return $data();
        }

        $notCached = new stdClass;

        $cached = Cache::get($key, $notCached);

        if ($cached !== $notCached) {
            return $cached;
        }

        $policy = Gate::getPolicyFor($model);

        $configuredTtl = config('restify.cache.policies.ttl', 60);

        $ttl = $policy instanceof Cacheable
            ? $policy->cache()
            : (is_int($configuredTtl) ? $configuredTtl : null);

        $result = $data();

        if (is_null($ttl)) {
            return $result;
        }

        Cache::put($key, $result, $ttl);

        return $result;
    }
}
