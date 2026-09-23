<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RepositoryDestroyBulkController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryDestroyBulkRequest $request)
    {
        $keys = $request->isJson() ? $request->json()->all() : $request->post();

        // A missing/null key would otherwise be silently dropped before the
        // lookup and 404 the whole request instead of naming the bad row.
        Validator::make(['keys' => $keys], ['keys.*' => ['required']])->validate();

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

        $request->repository()::deletedBulk(Collection::make($deleted), $request);

        return ok();
    }
}
