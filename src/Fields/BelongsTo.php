<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Database\Eloquent\Model;

class BelongsTo extends EagerField
{
    public ?Closure $storeParentCallback;

    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid parent repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->parentRepository = $parentRepository;
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

        $this->value = $this->parentRepository::resolveWith(
            $model->{$this->relation}()->first()
        );
    }
}
