<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\ResolvesBulkModels;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyBulkRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepositoryDestroyBulkController
{
    use ResolvesBulkModels;

    public function __invoke(RepositoryDestroyBulkRequest $request): JsonResponse
    {
        $rawKeys = $request->isJson() ? $request->json()->all() : $request->post();

        /** @var ValidatorContract $validator */
        $validator = $request->repository()::validatorForDestroyBulk($request, ['keys' => $rawKeys]);
        $validator->validate();

        /** @var array<int, int|string> $keys */
        $keys = $rawKeys;

        $deleted = [];

        DB::transaction(function () use ($request, $keys, &$deleted): void {
            $models = $this->resolveBulkModels($request, $keys);

            $authorized = [];

            foreach ($models as $row => $model) {
                /** @var int|string $key */
                $key = $model->getKey();

                $authorized[$key] ??= [
                    $keys[$row],
                    (int) $row,
                    $request->repositoryWith($model)->allowToDestroyBulk($request),
                ];
            }

            foreach ($authorized as [$key, $row, $repository]) {
                $deleted[] = $repository->resource->attributesToArray();

                $repository->deleteBulk($request, $key, $row);
            }
        });

        $request->repository()::deletedBulk(Collection::make($deleted), $request);

        /** @var JsonResponse $response */
        $response = ok();

        return $response;
    }
}
