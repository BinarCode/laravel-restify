<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\DeletesFields;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Symfony\Component\HttpFoundation\Response;

class RepositoryDestroyController extends RepositoryController
{
    use DeletesFields;

    public function __invoke(RepositoryDestroyRequest $request): Response
    {
        $repository = $request->repositoryWith(
            $model = $request->modelQuery()->firstOrFail()
        )->allowToDestroy($request);

        $this->deleteFields($request, $model);

        return $repository->destroy($request, request('repositoryId'));
    }
}
