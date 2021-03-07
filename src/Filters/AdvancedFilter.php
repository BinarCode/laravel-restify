<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Validation\ValidationException;

abstract class AdvancedFilter extends Filter
{
    /**
     * This is the value resolved from the frontend when applying the filter.
     *
     * @var AdvancedFilterPayloadDto
     */
    public AdvancedFilterPayloadDto $value;

    public function resolve(RestifyRequest $request, AdvancedFilterPayloadDto $dto): self
    {
        $this->value = $dto;

        return $this;
    }

    public function validatePayload(RestifyRequest $request, array $payload): self
    {
        if ($this instanceof SelectFilter) {
            foreach (array_keys($payload) as $key) {
                throw_unless(
                    in_array($key, array_values($this->options($request))),
                    ValidationException::withMessages(['Filter values are invalid.'])
                );
            }
        }

        return $this;
    }

    protected function input(string $key = null, $default = null)
    {
        return $this->value->input($key, $default);
    }
}
