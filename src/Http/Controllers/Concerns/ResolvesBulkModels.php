<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

trait ResolvesBulkModels
{
    /**
     * @param  Collection<int, int|string>  $keys
     * @return Collection<int|string, Model>
     */
    protected function resolveBulkModels(RestifyRequest $request, Collection $keys): Collection
    {
        $model = $request->model();

        $models = $request->modelsQuery($keys->all())
            ->lockForUpdate()
            ->get()
            ->keyBy($model->getRouteKeyName());

        $missing = array_values(array_diff($keys->all(), $models->keys()->all()));

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, $missing);
        }

        return $models;
    }
}
