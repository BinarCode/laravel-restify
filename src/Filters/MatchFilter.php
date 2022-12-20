<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class MatchFilter extends Filter
{
    public static $uriKey = 'matches';

    public bool $negation = false;

    private Closure $resolver;

    public const TYPE = 'matchable';

    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $request, $query, $value);
        }

        $field = $this->column;

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
}
