<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    public function __invoke(RepositoryUpdateBulkRequest $request)
    {
        // Validate ALL items upfront with correct indices
        $request->repository()::validatorForUpdateBulk($request)->validate();

        $collection = DB::transaction(function () use ($request) {
            return $request->collectInput()
                ->each(function (array $item, int $row) use ($request) {
                    $model = $request->modelQuery(
                        $id = $item['id']
                    )->lockForUpdate()->firstOrFail();

                    /** @var Repository $repository */
                    $repository = $request->repositoryWith($model);

                    // Authorization only (validation done upfront)
                    $repository->authorizeToUpdateBulk($request);

                    return $repository->updateBulk($request, $id, $row);
                });
        });

        $request->repository()::savedBulk($collection, $request);
        $request->repository()::updatedBulk($collection, $request);

        return $this->response()
            ->success();
    }
}
