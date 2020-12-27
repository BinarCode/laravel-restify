<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Controllers\Concerns\DeletesFields;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;

class RepositoryDestroyController extends RepositoryController
{
    use DeletesFields;

    public function __invoke(RepositoryDestroyRequest $request)
    {
        $repository = $request->newRepositoryWith(
            $model = $request->findModelQuery()->firstOrFail()
        )->allowToDestroy($request);

        $this->deleteFields($request, $model);

        return $repository->destroy($request, request('repositoryId'));
    }
}
