<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request)
    {
        if ($repository = $this->guessRepository($request)) {
            return $repository->serializeForShow($request);
        }

        $user = $request->user();

        if (isset($user->{ProfileAvatarRequest::$userAvatarAttribute})) {
            $user->{ProfileAvatarRequest::$userAvatarAttribute} = url($user->{ProfileAvatarRequest::$userAvatarAttribute});
        }

        if ($related = $request->input('related')) {
            $user->load(explode(',', $related));
        }

        $meta = [];

        if (method_exists($user, 'profile')) {
            $meta = (array) call_user_func([$user, 'profile'], $request);
        }

        return $this->response()
            ->data($user)
            ->meta($meta);
    }

    public function guessRepository(RestifyRequest $request): ?Repository
    {
        $repository = $request->repository('users');

        if (! $repository) {
            return null;
        }

        if (method_exists($repository, 'canUseForProfile')) {
            if (! call_user_func([$repository, 'canUseForProfile'], $request)) {
                return null;
            }
        }

        $user = tap(RepositorySearchService::instance()->search(
            $request, $repository
        ), function ($query) use ($request, $repository) {
            $repository::indexQuery($request, $query);
        })->whereKey(Auth::id())->firstOrFail();

        return $repository->withResource($user);
    }
}
