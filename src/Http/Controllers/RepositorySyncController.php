<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositorySyncRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Support\Arr;

class RepositorySyncController extends RepositoryController
{
    use InteractsWithAttachers;

    public function __invoke(RepositorySyncRequest $request)
    {
        $model = $request->findModelOrFail();
        $repository = $request->repository()->withResource($model);

        if (is_callable(
            $method = $this->authorizeBelongsToMany($request)->guessAttachMethod($request)
        )) {
            return call_user_func($method, $request, $repository, $model);
        }

        return $repository->sync(
            $request,
            $request->repositoryId,
            collect(Arr::wrap($request->input($request->relatedRepository)))
                ->flatten()
                ->filter(fn ($relatedRepositoryId) => $request
                    ->repositoryWith(
                        $request->modelQuery()->firstOrFail()
                    )
                    ->allowToSync($request, attachers: collect(Arr::wrap($request->input($request->relatedRepository))))
                )
        );
    }
}
