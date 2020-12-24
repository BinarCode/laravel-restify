<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class PivotsCollection extends Collection
{
    public function resolveFromPivot(Pivot $pivot): self
    {
        return $this->map(function (Field $field) use ($pivot) {
            return $field->resolveCallback(fn () => $pivot->{$field->attribute});
        });
    }
}
