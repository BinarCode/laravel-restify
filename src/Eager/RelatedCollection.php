<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;

class RelatedCollection extends Collection
{
    public function intoAssoc(): self
    {
        return $this->mapWithKeys(function ($value, $key) {
            return [
                is_numeric($key) ? $value : $key => $value,
            ];
        });
    }

    public function forEager(RestifyRequest $request): self
    {
        return $this->filter(fn ($value, $key) => $value instanceof EagerField)
            ->filter(fn (Field $field) => $field->authorize($request))
            ->unique('attribute');
    }

    public function mapIntoSortable(RestifyRequest $request): self
    {
        return $this->filter(fn (EagerField $field) => $field->isSortable())
            //Now we support only belongs to sort from related.
            ->filter(fn (EagerField $field) => $field instanceof BelongsTo)
            ->map(fn (BelongsTo $field) => SortableFilter::make()->usingBelongsTo($field));
    }

    public function inRequest(RestifyRequest $request): self
    {
        return $this
            ->filter(fn ($field, $key) => in_array($key, str_getcsv($request->input('related'))))
            ->unique();
    }

    public function mapIntoRelated(RestifyRequest $request)
    {
        return $this->map(function ($value, $key) {
            return Related::make($key, $value instanceof EagerField ? $value : null);
        });
    }

    public function authorized(RestifyRequest $request)
    {
        return $this->intoAssoc()
            ->filter(fn ($key, $value) => $key instanceof EagerField ? $key->authorize($request) : true);
    }
}
