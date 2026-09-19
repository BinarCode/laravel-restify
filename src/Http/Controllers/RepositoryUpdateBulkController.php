<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryUpdateBulkRequest $request)
    {
        // Validate ALL items upfront with correct indices
        $request->repository()::validatorForUpdateBulk($request)->validate();

        $collection = DB::transaction(function () use ($request) {
            $input = $request->collectInput();

            $models = $this->resolveBulkModels($request, $input->pluck('id'));

            return $input->each(function (array $item, int $row) use ($request, $models) {
                /** @var Repository $repository */
                $repository = $request->repositoryWith($models->get($id = $item['id']));

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
