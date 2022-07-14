<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryDestroyBulkController
{
    public function __invoke(RepositoryDestroyBulkRequest $request)
    {
        $collection = DB::transaction(function () use ($request) {
            return $request->collect()
                ->each(function (int|string $key, int $row) use ($request) {
                    $model = $request->modelQuery($key)->lockForUpdate()->firstOrFail();

                    /**
                     * @var Repository $repository
                     */
                    $repository = $request->repositoryWith($model);

                    return $repository
                        ->allowToDestroyBulk($request)
                        ->deleteBulk(
                            $request,
                            $key,
                            $row
                        );
                });
        });

        $request->repository()::deletedBulk($collection, $request);

        return ok();
    }
}
