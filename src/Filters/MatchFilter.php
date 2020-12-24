<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Str;

class MatchFilter extends Filter
{
    public static $uriKey = 'matches';

    public function filter(RestifyRequest $request, $query, $value)
    {
        $key = Str::afterLast($this->column, '.');
        $negation = false;

        if ($request->has('-'.$key)) {
            $negation = true;
        }

        if (empty($value)) {
            return $query;
        }

        $match = $value;

        if ($negation) {
            $key = Str::after($key, '-');
        }

        $field = $this->column;

        if ($match === 'null') {
            if ($negation) {
                $query->whereNotNull($field);
            } else {
                $query->whereNull($field);
            }
        } else {
            switch ($this->getType()) {
                case RestifySearchable::MATCH_TEXT:
                case 'string':
                    $query->where($field, $negation ? '!=' : '=', $match);
                    break;
                case RestifySearchable::MATCH_BOOL:
                case 'boolean':
                    if ($match === 'false') {
                        $query->where(function ($query) use ($field, $negation) {
                            if ($negation) {
                                return $query->where($field, true);
                            } else {
                                return $query->where($field, '=', false)->orWhereNull($field);
                            }
                        });
                        break;
                    }
                    $query->where($field, $negation ? '!=' : '=', true);
                    break;
                case RestifySearchable::MATCH_INTEGER:
                case 'number':
                case 'int':
                    $query->where($field, $negation ? '!=' : '=', (int) $match);
                    break;
                case RestifySearchable::MATCH_DATETIME:
                    $query->whereDate($field, $negation ? '!=' : '=', $match);
                    break;
                case RestifySearchable::MATCH_ARRAY:
                    $match = explode(',', $match);

                    if ($negation) {
                        $query->whereNotIn($field, $match);
                    } else {
                        $query->whereIn($field, $match);
                    }
                    break;
            }
        }
    }
}
