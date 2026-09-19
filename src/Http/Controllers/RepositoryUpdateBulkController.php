<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryUpdateBulkRequest $request)
    {
        // Validate ALL items upfront with correct indices
        $request->repository()::validatorForUpdateBulk($request)->validate();

        $collection = $request->collectInput();

        DB::transaction(function () use ($request, $collection): void {
            $input = $collection->all();

            $models = $this->resolveBulkModels($request, array_column($input, 'id'));

            foreach ($input as $row => $item) {
                $repository = $request->repositoryWith($models[$item['id']]);

                // Authorization only (validation done upfront)
                $repository->authorizeToUpdateBulk($request);

                $repository->updateBulk($request, $item['id'], $row);
            }
        });

        $request->repository()::savedBulk($collection, $request);
        $request->repository()::updatedBulk($collection, $request);

        return $this->response()
            ->success();
    }
}
