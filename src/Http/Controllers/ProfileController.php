<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileRequestRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ProfileController extends RepositoryController
{
    public function __invoke(ProfileRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        $repository = $this->guessRepository($request);

        if ($repository === null) {
            return data($user);
        }

        // Nobody is authenticated: there is no profile to show, and the answer stays the 404 it always was.
        if ($user === null) {
            throw (new ModelNotFoundException)->setModel($repository::newModel()::class);
        }

        // The row is the authenticated user itself, matched on its primary key. A user that is not a row of the
        // repository, such as an admin of another guard, is serialized as it is: its id names no row here.
        $key = $repository::profileKey($user);

        if ($key === null) {
            return data($user);
        }

        $withs = $repository::collectWiths($request, $repository)->all();

        return $request->repositoryWith(tap($request->newQuery('users')->whereKey($key),
            fn ($query) => $repository::showQuery(
                $request,
                $repository::mainQuery($request, $query->with($withs))
            ))->with($withs)->firstOrFail(), 'users')
            ->allowToShow($request)
            ->show($request, $key);
    }

    public function guessRepository(RestifyRequest $request): ?Repository
    {
        $repository = $request->repository('users');

        if (! $repository) {
            return null;
        }

        if (method_exists($repository, 'canUseForProfile') && ! call_user_func([$repository, 'canUseForProfile'],
            $request)) {
            return null;
        }

        return $repository;
    }
}
