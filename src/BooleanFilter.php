<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

abstract class BooleanFilter extends Filter
{
    public $type = 'boolean';


    public function resolve(RestifyRequest $request, $filter)
    {
        $keyValues = collect($this->options($request))->mapWithKeys(function ($key) use ($filter) {
            return [$key => data_get($filter, $key)];
        })->toArray();

        $this->value = $keyValues;
    }
}
