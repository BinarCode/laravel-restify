<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\LaravelData\Data;

class PaginationDataObject extends Data
{
    public function __construct(
        public int|string|null $perPage,
        public int|string|null $page,
    ) {}

    /**
     * Resolve the effective per-page value: the requested value when it is a
     * positive number, otherwise the repository default, clamped to
     * `restify.pagination.max_per_page` when that config is set.
     */
    public function resolvePerPage(int $default): int
    {
        $perPage = $this->positiveInt($this->perPage) ?? $default;

        $maxPerPage = $this->positiveInt(config('restify.pagination.max_per_page'));

        return $maxPerPage !== null ? min($perPage, $maxPerPage) : $perPage;
    }

    public function resolvePage(): ?int
    {
        return $this->positiveInt($this->page);
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
