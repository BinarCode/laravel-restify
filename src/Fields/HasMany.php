<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Model;

class HasMany extends EagerField
{
    public ?Closure $storeForParentCallback;

    public function storeForParent(RestifyRequest $request, Model $parent): self
    {
        if (is_callable($this->storeForParentCallback)) {
            call_user_func_array($this->storeForParentCallback, [
                $request,
                $parent
            ]);

            return $this;
        }

        $parent->{$this->attribute} = collect();

        collect(
            $request->input($this->attribute)
        )->each(function (array $data) use ($parent) {
            $parent->{$this->attribute}->push(
                $parent->{$this->relationship}()->create($data)
            );
        });

        return $this;
    }

    public function storeForParentCallback(callable $callback)
    {
        $this->storeForParentCallback = $callback;

        return $this;
    }
}

