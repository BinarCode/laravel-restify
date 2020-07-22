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
     * The repository index route default prefix.
     * @var string
     */
    public static $indexPrefix;

    public static function prefix(): ?string
    {
        return static::sanitizeSlashes(
            static::$prefix
        );
    }

    public static function indexPrefix(): ?string
    {
        return static::sanitizeSlashes(
            static::$indexPrefix
        );
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
}
