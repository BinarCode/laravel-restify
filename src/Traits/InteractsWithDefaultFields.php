<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait InteractsWithDefaultFields
{
    public static array $excludeFields = [];

    public function fields(RestifyRequest $request): array
    {
        return $this->getDefaultFields(
            exclude: static::$excludeFields
        );
    }

    public function fieldsForIndex(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForShow(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForUpdate(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    public function fieldsForStore(RestifyRequest $request): array
    {
        return $this->fields($request);
    }

    protected function getDefaultFields(array $exclude = []): array
    {
        return collect($this->model()->getAttributes())
            ->filter(fn($attribute, $field) => !in_array($field, $exclude))
            ->mapWithKeys(function ($value, $key) {
                $field = field($key);
                if (collect($this->model()->getCasts())->keys()->contains($key)) {
                    $field = $field->readOnly();
                }
                return [$key => $field];
            })->all();
    }
}
