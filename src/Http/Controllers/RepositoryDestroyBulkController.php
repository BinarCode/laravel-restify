<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryDestroyBulkController
{
    public function __invoke(RepositoryDestroyBulkRequest $request)
    {
        $repositories = collect();

        DB::transaction(function () use ($request, $repositories) {
            return $request->collect()
                ->each(function (int|string $key, int $row) use ($request, $repositories) {
                    $model = $request->modelQuery($key)->lockForUpdate()->firstOrFail();

                    $repositories->push($model->attributesToArray());

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

        $request->repository()::deletedBulk($repositories, $request);

        return ok();
    }
}
