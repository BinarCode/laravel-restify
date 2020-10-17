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

    public function authorizedUpdate(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToUpdate($request);
        })->values();
    }

    public function authorizedUpdateBulk(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToUpdateBulk($request);
        })->values();
    }

    public function authorizedStore(Request $request): self
    {
        return $this->filter(function (OrganicField $field) use ($request) {
            return $field->authorizedToStore($request);
        })->values();
    }

    public function resolve($repository): self
    {
        return $this->each(function ($field) use ($repository) {
            $field->resolve($repository);
        });
    }

    public function forIndex(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn(Field $field) => !$field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnIndex($request, $repository);
            })->values();
    }

    public function forShow(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn(Field $field) => !$field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnShow($request, $repository);
            })->values();
    }

    public function forStore(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn(Field $field) => !$field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnStore($request, $repository);
            })->values();
    }

    public function forStoreBulk(RestifyRequest $request, $repository): self
    {
        return $this->filter(function (Field $field) use ($repository, $request) {
            return $field->isShownOnStoreBulk($request, $repository);
        })->values();
    }

    public function forUpdate(RestifyRequest $request, $repository): self
    {
        return $this
            ->filter(fn(Field $field) => !$field instanceof EagerField)
            ->filter(function (Field $field) use ($repository, $request) {
                return $field->isShownOnUpdate($request, $repository);
            })->values();
    }

    public function forUpdateBulk(RestifyRequest $request, $repository): self
    {
        return $this->filter(function (Field $field) use ($repository, $request) {
            return $field->isShownOnUpdateBulk($request, $repository);
        })->values();
    }

    public function filterForManyToManyRelations(): self
    {
        return $this->filter(function ($field) {
            return $field instanceof BelongsToMany || $field instanceof MorphToMany;
        });
    }

    public function forEager(RestifyRequest $request): self
    {
        return $this
            ->filter(fn(Field $field) => $field instanceof EagerField)
            ->filter(fn(Field $field) => $field->authorize($request))
            ->unique();

    }
}
