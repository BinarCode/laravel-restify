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
    public AdvancedFilterPayloadDataObject $dataObject;

    public function resolve(RestifyRequest $request, AdvancedFilterPayloadDataObject $dataObject): self
    {
        $this->dataObject = $dataObject;

        return $this;
    }

    public function validatePayload(RestifyRequest $request, AdvancedFilterPayloadDataObject $dataObject): self
    {
        if (is_array($dataObject->value)) {
            Validator::make(
                $dataObject->value,
                $this->rules($request)
            )->validate();
        }

        return $this;
    }

    protected function input(?string $key = null, $default = null)
    {
        return data_get($this->dataObject->value, $key, $default);
    }

    protected function rest(?string $key = null, $default = null)
    {
        return data_get($this->dataObject->rest, $key, $default);
    }

    abstract public function rules(Request $request): array;
}
