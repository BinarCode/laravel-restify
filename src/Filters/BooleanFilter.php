<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

abstract class BooleanFilter extends AdvancedFilter
{
    public string $type = 'boolean';

    public function resolve(RestifyRequest $request, AdvancedFilterPayloadDto $dto): self
    {
        $keyValues = collect($this->options($request))->mapWithKeys(function ($key) use ($dto) {
            return [$key => data_get($dto->value(), $key)];
        })->toArray();

        $this->value = $dto;

        return $this;
    }
}
