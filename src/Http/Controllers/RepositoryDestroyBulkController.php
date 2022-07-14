<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryDestroyBulkController
{
    private array $repositories = [];

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

                    if (! in_array($repository, $this->repositories)) {
                        $this->repositories[] = $repository;
                    }

                    return $repository
                        ->allowToDestroyBulk($request)
                        ->deleteBulk(
                            $request,
                            $key,
                            $row
                        );
                });
        });

        /** @var Repository $repository */
        foreach ($this->repositories as $repository) {
            $repository::deletedBulk($collection, $request);
        }

        return ok();
    }
}
