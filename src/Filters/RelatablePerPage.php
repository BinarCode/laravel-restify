<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RequestLimits;

final class RelatablePerPage
{
    public static function isRequested(): bool
    {
        return request()->filled('relatablePerPage') || self::maxPerPageIsSet();
    }

    public static function forPath(string $relationPath, int $default): int
    {
        $requested = request('relatablePerPage');

        if (is_array($requested)) {
            $requested = $requested[$relationPath] ?? null;
        }

        return PaginationDataObject::fromInput($requested, null)->resolvePerPage($default);
    }

    private static function maxPerPageIsSet(): bool
    {
        return RequestLimits::maxPerPage() !== null;
    }
}
