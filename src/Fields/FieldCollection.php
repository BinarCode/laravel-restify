<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FieldCollection extends Collection
{
    public function authorized(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorize($request);
        })->values();
    }

    public function resolve($resource): self
    {
        return $this->each(function ($field) use ($resource) {
            $field->resolve($resource);
        });
    }

    public function forIndex(RestifyRequest $request, $resource): self
    {
        return $this->filter(function (Field $field) use ($resource, $request) {
            return $field->isShownOnIndex($request, $resource);
        })->values();
    }

    public function forShow(RestifyRequest $request, $resource): self
    {
        return $this->filter(function (Field $field) use ($resource, $request) {
            return $field->isShownOnShow($request, $resource);
        })->values();
    }

    public function forStore(RestifyRequest $request, $resource): self
    {
        return $this->filter(function (Field $field) use ($resource, $request) {
            return $field->isShownOnStore($request, $resource);
        })->values();
    }

    public function forUpdate(RestifyRequest $request, $resource): self
    {
        return $this->filter(function (Field $field) use ($resource, $request) {
            return $field->isShownOnUpdate($request, $resource);
        })->values();
    }
}
