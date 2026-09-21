<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryUpdateBulkRequest $request)
    {
        // Validate ALL items upfront with correct indices
        $request->repository()::validatorForUpdateBulk($request)->validate();

        $updated = DB::transaction(function () use ($request): array {
            $input = $request->collectInput()->all();

            $ids = [];

            foreach ($input as $row => $item) {
                $ids[$row] = $item['id'] ?? null;
            }

            $models = $this->resolveBulkModels($request, $ids);

            $authorized = [];

            // Authorization only (validation done upfront)
            foreach ($models as $row => $model) {
                $repository = $request->repositoryWith($model);

                $repository->authorizeToUpdateBulk($request);

                $authorized[] = [$ids[$row], $row, $repository];
            }

            $resources = [];

            foreach ($authorized as [$id, $row, $repository]) {
                $repository->updateBulk($request, $id, $row);

                $resources[] = $repository->resource;
            }

            return $resources;
        });

        $request->repository()::savedBulk(Collection::make($updated), $request);
        $request->repository()::updatedBulk(Collection::make($updated), $request);

        return $this->response()
            ->success();
    }
}
