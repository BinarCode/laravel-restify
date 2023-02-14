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
     * The repository prefixes by key.
     *
     * @var array
     */
    private static $prefixes;

    public static function prefix(): ?string
    {
        return static::hasPrefix()
            ? static::sanitizeSlashes(
                static::$prefixes[static::uriKey()]
            )
            : null;
    }

    /**
     * Determines whether a repository has prefix.
     */
    protected static function hasPrefix(): bool
    {
        $name = static::uriKey();

        return isset(static::$prefixes[$name]) && ! empty(static::$prefixes[$name]);
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

        if ($request->isIndexRequest()) {
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
        ])->some(fn ($prefix) => (bool) $prefix);
    }

    public static function setPrefix(?string $prefix, string $uriKey = null): void
    {
        static::$prefixes[$uriKey ?? static::uriKey()] = $prefix;
        static::$prefix = $prefix;
    }
}
