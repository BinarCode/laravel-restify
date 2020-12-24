<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Str;

trait WithRoutePrefix
{
    /**
     * The repository routes default prefix.
     *
     * @var string
     */
    public static $prefix;

    /**
     * List of index prefixes by uriKey.
     * @var array
     */
    public static $indexPrefixes;

    /**
     * The repository prefixes by key.
     *
     * @var array
     */
    private static $prefixes;

    /**
     * The repository index route default prefix.
     * @var string
     */
    public static $indexPrefix;

    public static function prefix(): ?string
    {
        return static::hasPrefix()
            ? static::sanitizeSlashes(
                static::$prefixes[static::uriKey()]
            )
            : null;
    }

    public static function indexPrefix(): ?string
    {
        return static::hasIndexPrefix()
            ? static::sanitizeSlashes(
                static::$indexPrefixes[static::uriKey()]
            )
            : null;
    }

    /**
     * Determines whether a repository has prefix.
     *
     * @return bool
     */
    protected static function hasPrefix(): bool
    {
        $name = static::uriKey();

        return isset(static::$prefixes[$name]) && ! empty(static::$prefixes[$name]);
    }

    public static function hasIndexPrefix(): bool
    {
        $name = static::uriKey();

        return isset(static::$indexPrefixes[$name]) && ! empty(static::$indexPrefixes[$name]);
    }

    protected static function sanitizeSlashes(?string $prefix): ?string
    {
        if ($prefix && Str::startsWith($prefix, '/')) {
            $prefix = Str::replaceFirst('/', '', $prefix);
        }

        if ($prefix && Str::endsWith($prefix, '/')) {
            $prefix = Str::replaceLast('/', '', $prefix);
        }

        return $prefix;
    }

    public static function authorizedToUseRoute(RestifyRequest $request): bool
    {
        if (! static::shouldAuthorizeRouteUsage()) {
            return true;
        }

        if ($request->isForRepositoryRequest()) {
            // index
            if (static::indexPrefix()) {
                return $request->is(static::indexPrefix().'/*');
            }

            if (static::prefix()) {
                return $request->is(static::prefix().'/*');
            }
        } else {
            // the rest
            return $request->is(static::prefix().'/*');
        }
    }

    protected static function shouldAuthorizeRouteUsage(): bool
    {
        return collect([
            static::prefix(),
            static::indexPrefix(),
        ])->some(fn ($prefix) => (bool) $prefix);
    }

    public static function setPrefix(string $prefix, string $uriKey = null)
    {
        if ($prefix) {
            static::$prefixes[$uriKey ?? static::uriKey()] = $prefix;
        }
    }

    public static function setIndexPrefix(string $prefix, string $uriKey = null)
    {
        if ($prefix) {
            static::$indexPrefixes[$uriKey ?? static::uriKey()] = $prefix;
        }
    }
}
