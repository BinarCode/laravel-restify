<?php

namespace Binaryk\LaravelRestify\Fields;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class HasOne extends EagerField
{
    public function __construct($attribute, $relation, $parentRepository)
    {
        if (! is_a(app($parentRepository), Repository::class)) {
            abort(500, "Invalid HasOne repository [{$parentRepository}]. Expended instance of " . Repository::class);
        }

        parent::__construct($attribute);

        $this->relation = $relation;
        $this->repositoryClass = $parentRepository;
    }

    public function fillAttribute(RestifyRequest $request, $model, int $bulkRow = null)
    {
        //
    }
}
