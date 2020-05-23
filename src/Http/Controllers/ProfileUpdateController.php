<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class ProfileUpdateController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        $user = $request->user();

        $request->validate([
            'email' => 'sometimes|required|unique:users,email,except,'.$user->id,
        ]);

        $user->update($request->only($user->getFillable()));

        return $this->response()->data($user->fresh());
    }
}
