<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileRequestRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends RepositoryController
{
    public function __invoke(ProfileRequestRequest $request): JsonResponse
    {
        if ($repository = $this->guessRepository($request)) {
            return $request->repositoryWith(tap($request->modelQuery(Auth::id(), 'users'),
                fn($query) => $repository::showQuery(
                    $request,
                    $repository::mainQuery($request,
                        $query->with($repository::collectWiths($request, $repository)->all()))
                ))->with($repository::collectWiths($request, $repository)->all())->firstOrFail(), 'users')
                ->allowToShow($request)
                ->show($request, Auth::id());

            return data($repository->serializeForShow($request));
        }

        return data($request->user());
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
