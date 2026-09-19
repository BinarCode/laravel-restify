<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesBulkModels
{
    /**
     * @param  list<int|string>  $keys
     * @return array<int|string, Model>
     */
    protected function resolveBulkModels(RestifyRequest $request, array $keys): array
    {
        $model = $request->model();

        $models = $request->modelsQuery($keys)
            ->lockForUpdate()
            ->get()
            ->keyBy($model->getRouteKeyName())
            ->all();

        $missing = array_values(array_diff($keys, array_keys($models)));

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, $missing);
        }

        return $models;
    }
}
