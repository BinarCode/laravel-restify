<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateBulkRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepositoryUpdateBulkController extends RepositoryController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryUpdateBulkRequest $request): JsonResponse
    {
        // Validate ALL items upfront with correct indices
        /** @var ValidatorContract $validator */
        $validator = $request->repository()::validatorForUpdateBulk($request);
        $validator->validate();

        $updated = DB::transaction(function () use ($request): array {
            /** @var array<int, array<string, mixed>> $input */
            $input = $request->collectInput()->all();

            /** @var array<int, int|string> $ids */
            $ids = [];

            foreach ($input as $row => $item) {
                /** @var int|string $id */
                $id = $item[Repository::BULK_ID_FIELD];

                $ids[$row] = $id;
            }

            $models = $this->resolveBulkModels($request, $ids);

            $authorized = [];

            // Authorization only (validation done upfront)
            foreach ($models as $row => $model) {
                $repository = $request->repositoryWith($model);

                $repository->authorizeToUpdateBulk($request);

                $authorized[] = [$ids[$row], (int) $row, $repository];
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
