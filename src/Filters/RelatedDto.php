<?php

namespace Binaryk\LaravelRestify\Filters;

use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

class RelatedDto extends DataTransferObject
{
    public array $related = [];

    public function getColumnsFor(string $relation): array|string
    {
        $related = collect($this->related)->first(fn ($related) => $relation === Str::before($related, '['));

        if (! (Str::contains($related, '[') && Str::contains($related, ']'))) {
            return '*';
        }

        $columns = explode(',', Str::replace('|', ',', Str::between($related, '[', ']')));

        return count($columns)
            ? $columns
            : '*';
    }
}
