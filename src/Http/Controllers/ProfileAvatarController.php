<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest;

class ProfileAvatarController extends RepositoryController
{
    public function __invoke(ProfileAvatarRequest $request)
    {
        $user = $request->user();

        $request->validate([
            $request::$userAvatarAttribute => 'required|image',
        ]);

        ProfileAvatarRequest::$path = "avatars/{$user->getKey()}";

        $path = is_callable(ProfileAvatarRequest::$pathCallback) ? call_user_func(ProfileAvatarRequest::$pathCallback, $request) : $request::$path;

        $path = $request->file($request::$userAvatarAttribute)->store($path);

        $user->{$request::$userAvatarAttribute} = $path;
        $user->save();


        $user->{ProfileAvatarRequest::$userAvatarAttribute} = url($user->{ProfileAvatarRequest::$userAvatarAttribute});

        return $this->response()->model($user);
    }
}
