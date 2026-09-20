<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Http\Controllers\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Collection;
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

        $lookup = array_unique(array_filter($keys, static fn (int|string|null $key): bool => $key !== null));

        /** @var Collection<int, Model> $loaded */
        $loaded = $request->modelsQuery($lookup)
            ->lockForUpdate()
            ->get();

        /** @var Collection<array-key, Model> $byRouteKey */
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
