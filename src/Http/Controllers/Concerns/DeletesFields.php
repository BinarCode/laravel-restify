<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\DeleteField;
use Illuminate\Database\Eloquent\Model;

trait DeletesFields
{
    protected function deleteFields(RestifyRequest $request, Model $model): void
    {
        DeleteField::pruneFields($request, $request->repositoryWith($model), $model);
    }
}
