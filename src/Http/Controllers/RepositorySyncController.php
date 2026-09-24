<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositorySyncRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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

        /** @var Collection<int, mixed> $attachers */
        $attachers = Collection::make(Arr::wrap($request->input($request->relatedRepositoryKey())));

        $request->repositoryWith($model)->allowToSync($request, attachers: $attachers);

        return $repository->sync(
            $request,
            $request->repositoryIdFromRoute(),
            $attachers->flatten()
        );
    }
}
