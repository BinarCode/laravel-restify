<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Fields\Concerns\CanSort;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BelongsTo extends EagerField implements Sortable
{
    use Attachable;
    use CanSort;

    public ?array $searchablesAttributes = null;

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
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

    public function searchable(...$attributes): self
    {
        $this->searchablesAttributes = collect($attributes)->flatten()->all();

        return $this;
    }

    public function isSearchable(): bool
    {
        return ! is_null($this->searchablesAttributes);
    }

    public function getSearchables(): array
    {
        return $this->searchablesAttributes;
    }
}
