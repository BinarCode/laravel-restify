<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class ProfileController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        $user = $request->user();

        if (isset($user->{ProfileAvatarRequest::$userAvatarAttribute})) {
            $user->{ProfileAvatarRequest::$userAvatarAttribute} = url($user->{ProfileAvatarRequest::$userAvatarAttribute});
        }

        return $this->response()->model($user);
    }
}
