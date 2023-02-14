<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Fields\Field;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class PivotsCollection extends Collection
{
    public function resolveFromPivot(Pivot $pivot): self
    {
        return $this->map(function (Field $field) use ($pivot) {
            return $field->resolveCallback(fn () => $pivot->{$field->attribute});
        });
    }
}
