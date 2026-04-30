<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BelongsTo extends EagerField implements Sortable
{
    use Attachable;

    public ?array $searchablesAttributes = null;

    public ?\Closure $searchableCallback = null;

    public function fillAttribute(RestifyRequest $request, $model, ?int $bulkRow = null)
    {
        /** * @var Model $relatedModel */
        $relatedModel = $model->{$this->relation}()->getModel();

        $belongsToModel = $relatedModel->newQuery()->whereKey(
            $request->input($this->attribute)
        )->firstOrFail();

        $methodGuesser = 'attach'.Str::studly(class_basename($relatedModel));

        $this->repository->authorizeToAttach(
            $request,
            $methodGuesser,
            $belongsToModel,
        );

        if (is_callable($this->canAttachCallback)) {
            if (! call_user_func($this->canAttachCallback, $request, $this->repository, $belongsToModel)) {
                abort(403, 'Unauthorized to attach.');
            }
        }

        $model->{$this->relation}()->associate(
            $belongsToModel
        );
    }

    /**
     * Override the parent searchable method to handle BelongsTo-specific multiple attributes
     */
    public function searchable(...$attributes): self
    {
        // Handle case where a single array is passed (legacy behavior)
        if (count($attributes) === 1 && is_array($attributes[0])) {
            $this->searchablesAttributes = collect($attributes[0])->flatten(1)->all();
            // Also call parent with the first attribute for consistency
            if (! empty($this->searchablesAttributes)) {
                $first = $this->searchablesAttributes[0];
                parent::searchable($first instanceof SearchableFilter ? ($first->column() ?? '') : $first);
            }

            return $this;
        }

        // If parent set a simple string column, also set it in searchablesAttributes for consistency
        if (count($attributes) === 1 && is_string($attributes[0])) {
            $this->searchablesAttributes = [$attributes[0]];

            parent::searchable($attributes[0]);

            return $this;
        }

        if (count($attributes) === 1 && $attributes[0] instanceof SearchableFilter) {
            $this->searchablesAttributes = [$attributes[0]];

            parent::searchable($attributes[0]->column() ?? '');

            return $this;
        }

        if (count($attributes) === 1 && is_callable($attributes[0])) {
            $this->searchableCallback = $attributes[0];

            return $this;
        }

        // If it's relationship-specific multiple attributes (all strings), use BelongsTo behavior
        if (count($attributes) > 1 && collect($attributes)->every(fn ($attr) => is_string($attr) || $attr instanceof SearchableFilter)) {
            $this->searchablesAttributes = collect($attributes)->flatten(1)->all();
            $first = $this->searchablesAttributes[0];
            // Also call parent to maintain consistency with CanSearch trait
            parent::searchable($first instanceof SearchableFilter ? ($first->column() ?? '') : $first);

            return $this;
        }

        // For single attribute or complex cases (closures, filters), use parent behavior
        parent::searchable(...$attributes);

        return $this;
    }

    /**
     * Check if this BelongsTo field is searchable (either via attributes or parent CanSearch)
     */
    public function isSearchable(?RestifyRequest $request = null): bool
    {
        if (is_callable($this->searchableCallback)) {
            return true;
        }

        return ! is_null($this->searchablesAttributes) || parent::isSearchable($request);
    }

    /**
     * Get the searchable attributes specific to BelongsTo relationships
     */
    public function getSearchables(): array
    {
        return $this->searchablesAttributes ?? [];
    }

    /**
     * Override parent getSearchColumn to provide BelongsTo-specific behavior
     */
    public function getSearchColumn(?RestifyRequest $request = null): mixed
    {
        // If we have BelongsTo-specific attributes, return the first one for compatibility
        if (! empty($this->searchablesAttributes)) {
            return $this->searchablesAttributes[0];
        }

        // Otherwise, use parent behavior
        return parent::getSearchColumn($request);
    }
}
