<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Illuminate\Support\Facades\DB;

class RepositoryDestroyBulkController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryDestroyBulkRequest $request)
    {
        $keys = $request->json()->all();
        $deleted = [];

        DB::transaction(function () use ($request, $keys, &$deleted): void {
            $models = $this->resolveBulkModels($request, $keys);

            $authorized = [];

            foreach ($models as $row => $model) {
                $authorized[$model->getKey()] ??= [
                    $keys[$row],
                    $row,
                    $request->repositoryWith($model)->allowToDestroyBulk($request),
                ];
            }

            foreach ($authorized as [$key, $row, $repository]) {
                $deleted[] = $repository->resource->attributesToArray();

                $repository->deleteBulk($request, $key, $row);
            }
        });

        $request->repository()::deletedBulk(collect($deleted), $request);

        return ok();
    }
}
