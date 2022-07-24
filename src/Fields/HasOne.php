<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\CanSort;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class HasOne extends EagerField implements Sortable
{
    use CanSort;

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        //
    }
}
