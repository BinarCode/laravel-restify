<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Fields\Concerns\CanSort;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class HasOne extends EagerField implements Sortable
{
    use CanSort;

    public function __construct($relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid HasOne repository [{$parentRepository}]. Expended instance of ".Repository::class);
        }

        parent::__construct(attribute: $relation);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;
    }

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        //
    }
}
