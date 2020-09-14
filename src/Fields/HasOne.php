<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\CreationAware;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;

class HasOne extends EagerField
{
    public $storeParentCallback;

    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid HasOne repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->parentRepository = $parentRepository;
    }

    public function storeChild(RestifyRequest $request, Model $parent): self
    {
        if (is_callable($this->storeParentCallback)) {
            call_user_func_array($this->storeParentCallback, [
                $request,
                $parent,
            ]);

            return $this;
        }

        $parent->{$this->attribute} = null;

        $repository = $this->parentRepository::resolveWith(
            $model = $parent->{$this->relation}()->getModel()
        )->allowToStore($request, $request->input($this->attribute));

        $model = new $model;

        if ($model instanceof CreationAware) {
            $model::createWithAttributes($request->input($this->attribute));

            return $this;
        }

        $parent->{$this->attribute} = $repository::resolveWith(
            $model->create($request->input($this->attribute))
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

        $this->value = $this->parentRepository::resolveWith(
            $model->{$this->relation}()->first()
        );
    }
}
