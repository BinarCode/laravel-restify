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
     * MCP schemas declare `perPage`/`page` as JSON numbers, which can decode as
     * float (e.g. `100.0`). Accept int, numeric string and float alike so the
     * MCP path honours the same values the REST query string does.
     */
    public static function fromInput(mixed $perPage, mixed $page): self
    {
        return new self(
            perPage: self::normalizeInput($perPage),
            page: self::normalizeInput($page),
        );
    }

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

    private static function normalizeInput(mixed $value): int|string|null
    {
        return match (true) {
            is_int($value), is_string($value) => $value,
            is_float($value) => (int) $value,
            default => null,
        };
    }
}
