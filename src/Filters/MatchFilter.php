<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchFilter extends Filter
{
    public static $uriKey = 'matches';

    public bool $negation = false;

    private Closure $resolver;

    public const TYPE = 'matchable';

    private ?EagerField $eagerField = null;

    public function filter(RestifyRequest $request, Builder|Relation $query, $value, bool $no_eager = false)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $request, $query, $value);
        }

        $field = $this->column;
        if ($this->eagerField instanceof EagerField && ! $no_eager) {
            $relation = $this->eagerField->getRelation($this->repository);
            $related = $this->eagerField->getRelatedModel($this->repository)->query();
            // TODO: This might be optimized more
            // Subquery has been done to avoid empty included and relationships responses.
            // The join method would work but it would return empty included and relationships arrays.
            // In the future, we should find a way to make the join method work (joins are commented below)
            if ($relation instanceof BelongsToMany) {
                $related->join($relation->getTable(), $relation->getQualifiedRelatedPivotKeyName(), '=', $relation->getQualifiedRelatedKeyName());
//                $related->join($this->repository->resource->getTable(), $relation->getQualifiedRelatedPivotKeyName(), '=', $relation->getQualifiedRelatedKeyName());
//                $related->where($relation->getQualifiedParentKeyName(), $this->eagerField->getRelatedModel($this->repository)->getKey());
                $related->where($relation->getQualifiedForeignPivotKeyName(), DB::raw($relation->getQualifiedParentKeyName()));
            } else {
                $related->where($relation->getQualifiedForeignKeyName(), DB::raw($relation->getQualifiedParentKeyName()));
//                $related->join($this->repository->resource->getTable(), $relation->getQualifiedParentKeyName(), '=', $relation->getQualifiedForeignKeyName());
            }
            $this->filter($request, $related, $value, true);
//            $relation_query = $this->filter($request, $relation->getQuery(), $value, true);
            $query->whereExists($related);
            return $query;
        }

        if ($value === 'null') {
            if ($this->negation) {
                $query->whereNotNull($field);
            } else {
                $query->whereNull($field);
            }
        } else {
            switch ($this->getType()) {
                case RestifySearchable::MATCH_TEXT:
                case 'string':
                    if ($this->negation) {
                        $query->where($field, $this->getNotLikeOperator(), $this->getNotLikeValue($value));
                    } else {
                        $query->where($field, $this->getLikeOperator(), $this->getLikeValue($value));
                    }

                    break;
                case RestifySearchable::MATCH_BOOL:
                case 'boolean':
                    if ($value === 'false') {
                        $query->where(function ($query) use ($field) {
                            if ($this->negation) {
                                return $query->where($field, true);
                            }

                            return $query->where($field, '=', false)->orWhereNull($field);
                        });

                        break;
                    }
                    $query->where($field, $this->negation ? '!=' : '=', true);

                    break;
                case RestifySearchable::MATCH_INTEGER:
                case 'number':
                case 'int':
                    $query->where($field, $this->negation ? '!=' : '=', (int) $value);

                    break;
                case RestifySearchable::MATCH_DATETIME:
                    if (count($values = explode(',', $value)) > 1) {
                        if ($this->negation) {
                            $query->whereNotBetween($field, $values);
                        } else {
                            $query->whereBetween($field, $values);
                        }
                    } else {
                        $query->whereDate($field, $this->negation ? '!=' : '=', $value);
                    }

                    break;
                case RestifySearchable::MATCH_BETWEEN:
                    if ($this->negation) {
                        $query->whereNotBetween($field, explode(',', $value));
                    } else {
                        $query->whereBetween($field, explode(',', $value));
                    }

                    break;
                case RestifySearchable::MATCH_ARRAY:
                    $value = explode(',', $value);

                    if ($this->negation) {
                        $query->whereNotIn($field, $value);
                    } else {
                        $query->whereIn($field, $value);
                    }

                    break;
            }
        }

        return $query;
    }

    public function getQueryKey(): ?string
    {
        if ($this->eagerField instanceof EagerField) {
            return $this->eagerField->getRelatedModel($this->repository)->getTable() . "." . parent::getQueryKey();
        }
        return parent::getQueryKey();
    }

    public function negate(): self
    {
        $this->negation = true;

        return $this;
    }

    public function syncNegation(): self
    {
        if (Str::startsWith($this->column, '-')) {
            $this->negate();

            $this->column = Str::after($this->column, '-');

            return $this;
        }

        return $this;
    }

    public function usingClosure(Closure $closure): self
    {
        $this->resolver = $closure;

        return $this;
    }

    public function usingRelated(EagerField $related): self
    {
        $this->eagerField = $related;

        return $this;
    }
}
