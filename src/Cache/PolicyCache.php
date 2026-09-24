<?php

namespace Binaryk\LaravelRestify\Cache;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use stdClass;
use Stringable;

class PolicyCache
{
    /**
     * Fallback ttl (seconds) used when `restify.cache.policies.ttl` is missing
     * from config, matching `config/restify.php`'s own `5 * 60` default.
     */
    final public const DEFAULT_TTL_SECONDS = 300;

    public static function enabled(): bool
    {
        return (bool) config('restify.cache.policies.enabled', false);
    }

    /**
     * Null means the current user has no stable identity to key a cache entry on
     * (a guest is stable and keyed on an empty segment; this is the unresolvable case).
     */
    public static function keyForAllowRestify(string $repositoryKey): ?string
    {
        $userKey = self::currentUserKey();

        if (is_null($userKey)) {
            return null;
        }

        return "restify.policy.allowRestify.repository-$repositoryKey.user-$userKey";
    }

    /**
     * Null means the entry must not be cached: either the model has no key yet
     * (nothing would ever read it back) or the user has no stable identity.
     */
    public static function keyForPolicyMethods(string $repositoryKey, string $policyMethod, string|int|null $modelKey): ?string
    {
        if (is_null($modelKey)) {
            return null;
        }

        $userKey = self::currentUserKey();

        if (is_null($userKey)) {
            return null;
        }

        return "restify.policy.$policyMethod.repository-$repositoryKey.resource-$modelKey.user-$userKey";
    }

    /**
     * @param  string|Closure(): (string|null)  $key  Built lazily so it's never evaluated while caching is
     *                                                disabled - the default. A null key means the result
     *                                                must not be cached.
     */
    public static function resolve(string|Closure $key, callable|Closure $data, Model $model): mixed
    {
        if (! static::enabled()) {
            return $data();
        }

        $key = $key instanceof Closure ? $key() : $key;

        if (is_null($key)) {
            return $data();
        }

        $notCached = new stdClass;

        $cached = Cache::get($key, $notCached);

        if ($cached !== $notCached) {
            return $cached;
        }

        $policy = Gate::getPolicyFor($model);

        $configuredTtl = config('restify.cache.policies.ttl', self::DEFAULT_TTL_SECONDS);

        if ($policy instanceof Cacheable) {
            $ttl = $policy->cache();
        } elseif (is_numeric($configuredTtl)) {
            $ttl = (int) $configuredTtl;
        } else {
            $ttl = null;
        }

        $result = $data();

        if (is_null($ttl)) {
            return $result;
        }

        Cache::put($key, $result, $ttl);

        return $result;
    }

    /**
     * Null means the current user has no stable identity to key a cache entry on.
     * A guest is stable (an empty segment); an authenticated user with a null or
     * non-stringable auth identifier is not.
     */
    private static function currentUserKey(): ?string
    {
        $user = app(Request::class)->user();

        if (is_null($user)) {
            return '';
        }

        $identifier = self::identifierKey($user->getAuthIdentifier());

        if (is_null($identifier)) {
            return null;
        }

        return $user::class.'#'.$identifier;
    }

    private static function identifierKey(mixed $id): ?string
    {
        return match (true) {
            is_scalar($id) => (string) $id,
            $id instanceof Stringable => (string) $id,
            default => null,
        };
    }
}
