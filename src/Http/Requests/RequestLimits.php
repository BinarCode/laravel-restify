<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests;

/**
 * The ceilings on how much work one request may ask for.
 *
 * The page size cap is opt-in: only a positive `restify.pagination.max_per_page` caps it.
 *
 * The bulk row and related depth limits have package defaults, applied here so an application whose
 * published `config/restify.php` predates their keys is still limited. For each of those keys:
 *
 * - `null`, a missing key, an empty string or any other non-numeric value: the package default;
 * - a positive number: that limit;
 * - `0`, a negative number or `false`: no limit.
 */
final class RequestLimits
{
    public const DEFAULT_MAX_BULK_ROWS = 1000;

    public const DEFAULT_MAX_RELATED_DEPTH = 5;

    /**
     * The largest page size `perPage`, `page[size]` or `relatablePerPage` may resolve to, or `null`
     * for no cap.
     *
     * Only a positive number sets a cap. `null`, a missing key, an empty string, any other
     * non-numeric value, `0`, a negative number and `false` all mean no cap.
     */
    public static function maxPerPage(): ?int
    {
        $configured = self::configured('restify.pagination.max_per_page');

        return is_int($configured) ? $configured : null;
    }

    /**
     * The most rows a bulk store, bulk update or bulk delete request may carry.
     */
    public static function maxBulkRows(): ?int
    {
        return self::limit('restify.bulk.max_rows', self::DEFAULT_MAX_BULK_ROWS);
    }

    /**
     * The most segments a `related` / `include` path may have: `posts.comments.user` has three.
     */
    public static function maxRelatedDepth(): ?int
    {
        return self::limit('restify.related.max_depth', self::DEFAULT_MAX_RELATED_DEPTH);
    }

    private static function limit(string $key, int $default): ?int
    {
        $configured = self::configured($key);

        return $configured === false ? null : ($configured ?? $default);
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
