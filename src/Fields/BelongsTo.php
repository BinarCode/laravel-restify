<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BelongsTo extends EagerField
{
    public ?Closure $storeParentCallback;

    /**
     * @var Closure
     */
    private Closure $canAttachCallback;

    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid parent repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;
    }

    public function storeParent(RestifyRequest $request, Model $child): self
    {
        if (is_callable($this->storeParentCallback)) {
            call_user_func_array($this->storeParentCallback, [
                $request,
                $child,
            ]);

            return $this;
        }

        $child->{$this->attribute} = null;

        $child->{$this->attribute} = $child->{$this->relation}()->create(
            $request->input($this->attribute)
        );

        return $this;
    }

    public function storeParentCallback(callable $callback)
    {
        $this->storeParentCallback = $callback;

        return $this;
    }

    public function resolve($repository, $attribute = null)
    {
        $model = $repository->resource;

        $this->value = $this->repositoryClass::resolveWith(
            $model->{$this->relation}()->first()
        );
    }

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
                abort(401, 'Unauthorized to attach.');
            }
        }

        $model->{$this->relation}()->associate(
            $belongsToModel
        );
    }

    public function canAttach(Closure $callback)
    {
        $this->canAttachCallback = $callback;

        return $this;
    }
}
