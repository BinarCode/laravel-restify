<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests;

/**
 * The ceilings on how much work one request may ask for.
 *
 * The page size cap and the bulk row limit are opt-in: only a positive number in
 * `restify.pagination.max_per_page` or `restify.bulk.max_rows` sets them. `null`, a missing key, an
 * empty string, any other non-numeric value, `0`, a negative number and `false` all mean no limit.
 *
 * The related depth limit has a package default, applied here so an application whose published
 * `config/restify.php` predates `restify.related.max_depth` is still limited:
 *
 * - `null`, a missing key, an empty string or any other non-numeric value: the package default;
 * - a positive number: that limit;
 * - `0`, a negative number or `false`: no limit.
 */
final class RequestLimits
{
    public const DEFAULT_MAX_RELATED_DEPTH = 5;

    /**
     * The largest page size `perPage`, `page[size]` or `relatablePerPage` may resolve to, or `null`
     * for no cap.
     */
    public static function maxPerPage(): ?int
    {
        return self::optIn('restify.pagination.max_per_page');
    }

    /**
     * The most rows a bulk store, bulk update or bulk delete request may carry, or `null` for no limit.
     */
    public static function maxBulkRows(): ?int
    {
        return self::optIn('restify.bulk.max_rows');
    }

    /**
     * The most segments a `related` / `include` path may have: `posts.comments.user` has three.
     */
    public static function maxRelatedDepth(): ?int
    {
        $configured = self::configured('restify.related.max_depth');

        return $configured === false ? null : ($configured ?? self::DEFAULT_MAX_RELATED_DEPTH);
    }

    /**
     * A limit that only applies when config sets it to a positive number.
     */
    private static function optIn(string $key): ?int
    {
        $configured = self::configured($key);

        return is_int($configured) ? $configured : null;
    }

    /**
     * The limit set in config, `false` when it is turned off, `null` when none is set.
     */
    private static function configured(string $key): int|false|null
    {
        $value = config($key);

        if ($value === false) {
            return false;
        }

        // Casting a float that does not fit an int throws on PHP 8.5; its string form saturates.
        if (is_float($value)) {
            $value = (string) $value;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : false;
    }
}
