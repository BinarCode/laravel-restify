<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileUpdateController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        $user = $request->user();

        if ($repository = $this->guessRepository($request)) {
            $repository->allowToUpdate($request)->update($request, Auth::id());
        } else {
            $request->validate([
                'email' => 'sometimes|required|unique:users,email,'.$user->id,
                'password' => 'sometimes|required|min:5|confirmed',
            ]);

            if ($request->has('password')) {
                $request->merge([
                    'password' => Hash::make($request->get('password')),
                ]);
            }

            $user->update($request->only($user->getFillable()));
        }

        return $this->response()->data($user->fresh());
    }

    public function guessRepository(RestifyRequest $request): ?Repository
    {
        $repository = $request->repository('users');

        if (! $repository) {
            return null;
        }

        if (method_exists($repository, 'canUseForProfileUpdate')) {
            if (!call_user_func([$repository, 'canUseForProfileUpdate'], $request)) {
                return null;
            }
        }

        $repository->withResource(
            $repository::query($request)->whereKey(Auth::id())->first()
        );

        return $repository;
    }
}
