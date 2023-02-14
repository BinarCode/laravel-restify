<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class AdvancedFilter extends Filter
{
    /**
     * This is the value resolved from the frontend when applying the filter.
     */
    public AdvancedFilterPayloadDto $dto;

    public function resolve(RestifyRequest $request, AdvancedFilterPayloadDto $dto): self
    {
        $this->dto = $dto;

        return $this;
    }

    public function validatePayload(RestifyRequest $request, AdvancedFilterPayloadDto $dto): self
    {
        if (is_array($dto->value)) {
            Validator::make(
                $dto->value,
                $this->rules($request)
            )->validate();
        }

        return $this;
    }

    protected function input(string $key = null, $default = null)
    {
        return data_get($this->dto->value, $key, $default);
    }

    abstract public function rules(Request $request): array;
}
