<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;

class ProfileController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        if ($repository = $this->guessRepository($request)) {
            return $repository;
        }

        $user = $request->user();

        if (isset($user->{ProfileAvatarRequest::$userAvatarAttribute})) {
            $user->{ProfileAvatarRequest::$userAvatarAttribute} = url($user->{ProfileAvatarRequest::$userAvatarAttribute});
        }

        if ($related = $request->get('related')) {
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

        $user = tap(RepositorySearchService::instance()->search(
            $request, $repository
        ), function ($query) use ($request, $repository) {
            $repository::indexQuery($request, $query);
        })->firstOrFail();

        return $repository->withResource($user);
    }
}
