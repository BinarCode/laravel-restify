<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Model;

class BelongsTo extends EagerField
{
    public ?Closure $storeParentCallback;

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

        $child->{$this->attribute} = $child->{$this->relationship}()->create(
            $request->input($this->attribute)
        );

        return $this;
    }

    public function storeParentCallback(callable $callback)
    {
        $this->storeParentCallback = $callback;

        return $this;
    }
}
