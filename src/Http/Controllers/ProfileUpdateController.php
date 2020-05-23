<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Facades\Hash;

class ProfileUpdateController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        $user = $request->user();

        $request->validate([
            'email' => 'sometimes|required|unique:users,email,except,'.$user->id,
            'password' => 'sometimes|required|min:5|confirmed',
        ]);

        if ($request->has('password')) {
            $request->merge([
                'password' => Hash::make($request->get('password')),
            ]);
        }

        $user->update($request->only($user->getFillable()));

        return $this->response()->data($user->fresh());
    }
}
