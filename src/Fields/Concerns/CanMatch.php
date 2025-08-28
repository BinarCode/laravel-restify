<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait CanMatch
{
    protected mixed $matchableColumn = null;

    protected ?string $matchableType = null;

    public function matchable(mixed $column = null, ?string $type = null): self
    {
        if ($column === false) {
            $this->matchableColumn = null;
            $this->matchableType = null;

            return $this;
        }

        if (is_callable($column)) {
            $this->matchableColumn = $column;
            $this->matchableType = 'custom';

            return $this;
        }

        if ($column instanceof MatchFilter) {
            $this->matchableColumn = $column;
            $this->matchableType = 'custom';

            return $this;
        }

        $this->matchableColumn = $column ?? $this->getAttribute();
        $this->matchableType = $type ?? $this->guessMatchType();

        return $this;
    }

    public function matchableCallback(callable $callback): self
    {
        return $this->matchable($callback, 'custom');
    }

    public function matchableText(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_TEXT);
    }

    public function matchableBool(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_BOOL);
    }

    public function matchableBoolean(?string $column = null): self
    {
        return $this->matchableBool($column);
    }

    public function matchableInteger(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_INTEGER);
    }

    public function matchableInt(?string $column = null): self
    {
        return $this->matchableInteger($column);
    }

    public function matchableDatetime(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_DATETIME);
    }

    public function matchableDate(?string $column = null): self
    {
        return $this->matchableDatetime($column);
    }

    public function matchableBetween(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_BETWEEN);
    }

    public function matchableArray(?string $column = null): self
    {
        return $this->matchable($column, RestifySearchable::MATCH_ARRAY);
    }

    public function isMatchable(RestifyRequest $request = null): bool
    {
        if (is_callable($this->matchableColumn)) {
            return true;
        }

        return ! is_null($this->matchableColumn);
    }

    public function getMatchColumn(RestifyRequest $request = null): mixed
    {
        if (! $this->isMatchable($request)) {
            return null;
        }

        return $this->matchableColumn;
    }

    public function getMatchType(RestifyRequest $request = null): ?string
    {
        if (! $this->isMatchable($request)) {
            return null;
        }

        return $this->matchableType;
    }

    protected function guessMatchType(): string
    {
        // Use field type detection from Field class if available
        if (method_exists($this, 'guessFieldType')) {
            $fieldType = $this->guessFieldType();

            return match ($fieldType) {
                'boolean' => RestifySearchable::MATCH_BOOL,
                'number' => RestifySearchable::MATCH_INTEGER,
                'array' => RestifySearchable::MATCH_ARRAY,
                default => RestifySearchable::MATCH_TEXT,
            };
        }

        // Fallback to attribute name patterns
        $attribute = $this->getAttribute();

        if (! is_string($attribute)) {
            return RestifySearchable::MATCH_TEXT;
        }

        $attribute = strtolower($attribute);

        // Boolean patterns
        if (preg_match('/^(is_|has_|can_|should_|will_|was_|were_)/', $attribute) ||
            in_array($attribute,
                ['active', 'enabled', 'disabled', 'verified', 'published', 'featured', 'public', 'private'])) {
            return RestifySearchable::MATCH_BOOL;
        }

        // Number patterns
        if (preg_match('/_(id|count|number|amount|price|cost|total|sum|quantity|qty)$/', $attribute) ||
            in_array($attribute,
                ['id', 'age', 'year', 'month', 'day', 'hour', 'minute', 'second', 'weight', 'height', 'size'])) {
            return RestifySearchable::MATCH_INTEGER;
        }

        // Date patterns
        if (preg_match('/_(at|date|time)$/', $attribute) ||
            in_array($attribute,
                ['created_at', 'updated_at', 'deleted_at', 'published_at', 'birthday', 'date_of_birth'])) {
            return RestifySearchable::MATCH_DATETIME;
        }

        // Array patterns (JSON fields)
        if (preg_match('/_(json|data|metadata|config|settings|options|tags)$/', $attribute)) {
            return RestifySearchable::MATCH_ARRAY;
        }

        // Default to text matching
        return RestifySearchable::MATCH_TEXT;
    }
}
