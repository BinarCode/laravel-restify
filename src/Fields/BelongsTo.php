<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\Attachable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class BelongsTo extends EagerField
{
    use Attachable;

    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid parent repository [{$parentRepository}]. Expended instance of " . Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;
    }

    public function resolve($repository, $attribute = null)
    {
        $model = $repository->resource;

        $relatedModel = $model->{$this->relation}()->first();

        try {
            $this->value = $this->repositoryClass::resolveWith($relatedModel)
                ->allowToShow(app(Request::class))
                ->eagerState();
        } catch (AuthorizationException $e) {
            $class = get_class($relatedModel);
            $policy = get_class(Gate::getPolicyFor($relatedModel));

            abort(403, "You are not authorized to see the [{$class}] relationship from the BelongsTo field from the BelongsTo field. Check the [show] method from the [$policy]");
        }

        return $this;
    }

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        /** * @var Model $relatedModel */
        $relatedModel = $model->{$this->relation}()->getModel();

        $belongsToModel = $relatedModel->newQuery()->whereKey(
            $request->input($this->attribute)
        )->firstOrFail();

        $methodGuesser = 'attach' . Str::studly(class_basename($relatedModel));

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
}
