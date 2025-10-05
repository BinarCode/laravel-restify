<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;

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
        $this->matchableType = $type ?? $this->guessMatchType(
        // we'll use the store request to identify rules and guess types
            app(RepositoryStoreRequest::class)
        );

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

    protected function guessMatchType(RestifyRequest $request): string
    {
        $fieldType = $this->guessFieldType($request);

        return match (get_class($fieldType)) {
            ArrayType::class => RestifySearchable::MATCH_ARRAY,
            BooleanType::class => RestifySearchable::MATCH_BOOL,
            IntegerType::class, NumberType::class => RestifySearchable::MATCH_INTEGER,
            default => 'string',
        };
    }
}
