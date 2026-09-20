<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesBulkModels
{
    /**
     * @param  array<int|string, int|string|null>  $keys
     * @return array<int|string, Model>
     */
    protected function resolveBulkModels(RestifyRequest $request, array $keys): array
    {
        $model = $request->model();
        $routeKeyName = $model->getRouteKeyName();

        /** @var EloquentCollection<int, Model> $loaded */
        $loaded = $request->modelsQuery(array_values(array_unique($keys)))
            ->lockForUpdate()
            ->get();

        /** @var EloquentCollection<array-key, Model> $byRouteKey */
        $byRouteKey = $loaded->keyBy($routeKeyName);

        $integerRouteKey = $routeKeyName === $model->getKeyName() && $model->getKeyType() === 'int';

        $resolved = [];
        $missing = [];

        foreach ($keys as $row => $key) {
            $match = $byRouteKey->get($key);

            if ($match === null && $integerRouteKey) {
                $match = $loaded->first(
                    static fn (Model $candidate): bool => $candidate->getAttribute($routeKeyName) == $key
                );
            }

            if ($match instanceof Model) {
                $resolved[$row] = $match;

                continue;
            }

            $missing[] = $key;
        }

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel($model::class, $missing);
        }

        return $resolved;
    }
}
