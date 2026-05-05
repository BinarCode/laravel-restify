<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;

class SearchableFilter extends Filter
{
    public const TYPE = 'searchable';

    public const COLUMN_RAW = 'raw';

    public const COLUMN_UPPER = 'upper';

    public const COLUMN_LOWER = 'lower';

    public array $computedColumns = [];

    public BelongsTo $belongsToField;

    protected $customClosure = null;

    private ?string $columnWrap = null;

    /** @var (callable(string): string)|null */
    private $valueTransformer = null;

    public static function make(...$arguments): static
    {
        $filter = new static(...$arguments);

        if (isset($arguments[0]) && is_string($arguments[0])) {
            $filter->setColumn($arguments[0]);
        }

        return $filter;
    }

    public function transform(callable $transformer): self
    {
        $this->valueTransformer = $transformer;
        $this->columnWrap ??= self::COLUMN_RAW;

        return $this;
    }

    public function caseRaw(): self
    {
        $this->valueTransformer = static fn (string $value): string => $value;
        $this->columnWrap = self::COLUMN_RAW;

        return $this;
    }

    /**
     * Silent contract: rows stored in mixed/lower case will not match.
     */
    public function upperValue(): self
    {
        $this->valueTransformer = strtoupper(...);
        $this->columnWrap = self::COLUMN_RAW;

        return $this;
    }

    /**
     * Silent contract: rows stored in mixed/upper case will not match.
     */
    public function lowerValue(): self
    {
        $this->valueTransformer = strtolower(...);
        $this->columnWrap = self::COLUMN_RAW;

        return $this;
    }

    public function upperBoth(): self
    {
        $this->valueTransformer = strtoupper(...);
        $this->columnWrap = self::COLUMN_UPPER;

        return $this;
    }

    public function lowerBoth(): self
    {
        $this->valueTransformer = strtolower(...);
        $this->columnWrap = self::COLUMN_LOWER;

        return $this;
    }

    public function filter(RestifyRequest $request, $query, $value)
    {
        // Use custom closure if available
        if ($this->customClosure) {
            return call_user_func($this->customClosure, $request, $query, $value);
        }

        $connectionType = $this->repository->model()->getConnection()->getDriverName();

        $likeOperator = $connectionType === 'pgsql' ? 'ilike' : 'like';

        if (isset($this->belongsToField)) {
            if (! $this->belongsToField->authorize($request)) {
                return $query;
            }

            // Check if there's a custom searchable callback
            if (is_callable($this->belongsToField->searchableCallback)) {
                return call_user_func(
                    $this->belongsToField->searchableCallback,
                    $query,
                    $request,
                    $value,
                    $this->belongsToField,
                    $this->repository
                );
            }

            // Check if JOINs are enabled in config
            $useJoins = (bool) config('restify.search.use_joins_for_belongs_to', false);
            $relatedTable = $this->belongsToField->getRelatedModel($this->repository)->getTable();

            collect($this->belongsToField->getSearchables())->each(
                function (string|self $entry) use ($query, $value, $likeOperator, $connectionType, $useJoins, $relatedTable) {
                    [$column, $resolver] = $this->resolveBelongsToEntry($entry, $relatedTable, $useJoins);

                    if ($useJoins) {
                        // JOINs are applied at the service level, here we only emit search conditions
                        $this->applyValueAndColumn($query, $column, $value, $likeOperator, $connectionType, $resolver);

                        return;
                    }

                    $this->applyBelongsToSubquery($query, $column, $value, $likeOperator, $connectionType, $resolver);
                }
            );

            return $query;
        }

        $this->applyValueAndColumn($query, $this->column, $value, $likeOperator, $connectionType, $this);

        return $query;
    }

    public function usingBelongsTo(BelongsTo $field): self
    {
        $this->belongsToField = $field;

        return $this;
    }

    public function computed(...$columns): self
    {
        $this->computedColumns = collect($columns)->flatten()->all();

        return $this;
    }

    public function usingClosure(callable $closure): self
    {
        $this->customClosure = $closure;

        return $this;
    }

    public function hasCustomClosure(): bool
    {
        return ! is_null($this->customClosure);
    }

    public function hasBelongsTo(): bool
    {
        return isset($this->belongsToField);
    }

    public function resolvedTransformer(): Closure
    {
        if (is_callable($this->valueTransformer)) {
            return Closure::fromCallable($this->valueTransformer);
        }

        return config('restify.search.case_sensitive')
            ? static fn (string $value): string => $value
            : strtoupper(...);
    }

    public function resolvedColumnWrap(): string
    {
        if ($this->columnWrap !== null) {
            return $this->columnWrap;
        }

        return config('restify.search.case_sensitive')
            ? self::COLUMN_RAW
            : self::COLUMN_UPPER;
    }

    private function applyValueAndColumn(
        $query,
        string $column,
        string $value,
        string $likeOperator,
        string $connectionType,
        self $resolver,
    ): void {
        $transformedValue = ($resolver->resolvedTransformer())($value);
        $wrap = $resolver->resolvedColumnWrap();

        if ($wrap === self::COLUMN_RAW) {
            $query->orWhere($column, $likeOperator, '%'.$transformedValue.'%');

            return;
        }

        $columnExpression = $this->wrapColumn($column, $wrap, $connectionType);

        $query->orWhereRaw("{$columnExpression} LIKE ?", ['%'.$transformedValue.'%']);
    }

    private function applyBelongsToSubquery(
        $query,
        string $column,
        string $value,
        string $likeOperator,
        string $connectionType,
        self $resolver,
    ): void {
        $transformedValue = ($resolver->resolvedTransformer())($value);
        $wrap = $resolver->resolvedColumnWrap();

        $relatedModel = $this->belongsToField->getRelatedModel($this->repository);

        if ($wrap === self::COLUMN_RAW) {
            $query->orWhere(
                $relatedModel::select($column)
                    ->whereColumn(
                        $this->belongsToField->getQualifiedKey($this->repository),
                        $this->belongsToField->getRelatedKey($this->repository)
                    )
                    ->take(1),
                $likeOperator,
                '%'.$transformedValue.'%'
            );

            return;
        }

        $columnExpression = $this->wrapColumn($column, $wrap, $connectionType);

        // Pre-existing legacy: wrapped branch hardcodes 'like' (not $likeOperator).
        // Case-insensitivity comes from the UPPER/LOWER wrap, so on pgsql we don't need ILIKE here.
        // Note: on pgsql, the RAW branch above uses $likeOperator (= 'ilike'), so caseRaw()/upperValue()/
        // lowerValue() effectively match case-insensitively on pgsql via ILIKE on the raw column.
        $query->orWhere(
            $relatedModel::selectRaw($columnExpression)
                ->whereColumn(
                    $this->belongsToField->getQualifiedKey($this->repository),
                    $this->belongsToField->getRelatedKey($this->repository)
                )
                ->take(1),
            'like',
            '%'.$transformedValue.'%'
        );
    }

    private function wrapColumn(string $column, string $wrap, string $connectionType): string
    {
        $function = $wrap === self::COLUMN_LOWER ? 'LOWER' : 'UPPER';

        return $connectionType === 'pgsql'
            ? "{$function}({$column}::text)"
            : "{$function}({$column})";
    }

    /**
     * @return array{0: string, 1: self}
     */
    private function resolveBelongsToEntry(string|self $entry, string $relatedTable, bool $useJoins): array
    {
        if ($entry instanceof self) {
            $column = $entry->column();

            if ($column === null || $column === '') {
                throw new \InvalidArgumentException(
                    'SearchableFilter inside BelongsTo::searchable() has no column. '
                    .'Pass it as SearchableFilter::make(\'column\') or call setColumn() before passing.'
                );
            }

            if ($useJoins && ! str_contains($column, '.')) {
                $column = $relatedTable.'.'.$column;
            }

            return [$column, $entry];
        }

        $column = $useJoins
            ? (str_contains($entry, '.') ? $entry : $relatedTable.'.'.$entry)
            : $entry;

        return [$column, $this];
    }
}
