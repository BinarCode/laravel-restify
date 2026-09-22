<?php

namespace Binaryk\LaravelRestify\Cache;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use stdClass;
use Stringable;
use UnexpectedValueException;

class PolicyCache
{
    public static function enabled(): bool
    {
        return (bool) config('restify.cache.policies.enabled', false);
    }

    public static function keyForAllowRestify(string $repositoryKey): string
    {
        return "restify.policy.allowRestify.repository-$repositoryKey.user-".self::currentUserKey();
    }

    public static function keyForPolicyMethods(string $repositoryKey, string $policyMethod, string|int|null $modelKey): string
    {
        $modelKey = $modelKey ?? Str::random();

        return "restify.policy.$policyMethod.repository-$repositoryKey.resource-$modelKey.user-".self::currentUserKey();
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
            : (is_numeric($configuredTtl) ? (int) $configuredTtl : null);

        $result = $data();

        if (is_null($ttl)) {
            return $result;
        }

        Cache::put($key, $result, $ttl);

        return $result;
    }

    private static function currentUserKey(): string
    {
        $user = app(Request::class)->user();

        if (is_null($user)) {
            return '';
        }

        return $user::class.'#'.self::identifierKey($user->getAuthIdentifier());
    }

    private static function identifierKey(mixed $id): string
    {
        return match (true) {
            is_scalar($id) => (string) $id,
            $id instanceof Stringable => (string) $id,
            default => throw new UnexpectedValueException(
                'PolicyCache cannot build a cache key for a non-stringable auth identifier of type ['.get_debug_type($id).'].'
            ),
        };
    }
}
