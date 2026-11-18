<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Requests;

/**
 * The ceilings on how much work one request may ask for.
 *
 * Each limit is read from config and its default is applied here, so an application whose
 * published `config/restify.php` predates a key is still capped. For every key:
 *
 * - `null`, a missing key, an empty string or any other non-numeric value: the package default;
 * - a positive number: that limit;
 * - `0`, a negative number or `false`: no limit.
 */
final class RequestLimits
{
    public const DEFAULT_MAX_PER_PAGE = 100;

    public const DEFAULT_MAX_BULK_ROWS = 1000;

    public const DEFAULT_MAX_RELATED_DEPTH = 5;

    /**
     * The largest page size `perPage`, `page[size]` or `relatablePerPage` may resolve to.
     *
     * A limit set in config applies as is, and also clamps a repository default above it. The
     * package default only bounds what a client asks for, so it never goes below the page size
     * the repository itself declares.
     */
    public static function maxPerPage(int $repositoryDefault): ?int
    {
        $configured = self::configured('restify.pagination.max_per_page');

        if ($configured === false) {
            return null;
        }

        return $configured ?? max(self::DEFAULT_MAX_PER_PAGE, $repositoryDefault);
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
