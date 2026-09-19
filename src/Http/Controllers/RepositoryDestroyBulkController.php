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
        $keys = $request->input();
        $deleted = [];

        DB::transaction(function () use ($request, $keys, &$deleted): void {
            $models = $this->resolveBulkModels($request, $keys);

            foreach ($keys as $row => $key) {
                $model = $models[$key];

                $deleted[] = $model->attributesToArray();

                $request->repositoryWith($model)
                    ->allowToDestroyBulk($request)
                    ->deleteBulk($request, $key, $row);
            }
        });

        $request->repository()::deletedBulk(collect($deleted), $request);

        return ok();
    }
}
