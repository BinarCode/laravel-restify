<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\DeletesFields;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Illuminate\Http\JsonResponse;

class RepositoryDestroyController extends RepositoryController
{
    use DeletesFields;

    public function __invoke(RepositoryDestroyRequest $request): JsonResponse
    {
        $repository = $request->repositoryWith(
            $model = $request->findModelQuery()->firstOrFail()
        )->allowToDestroy($request);

        $this->deleteFields($request, $model);

        return $repository->destroy($request, request('repositoryId'));
    }
}
