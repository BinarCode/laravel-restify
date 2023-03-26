<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Routing\Controller;

class VerifyController extends Controller
{
    public function __invoke(int $id, string $hash)
    {
        /**
         * @var Authenticatable $user
         */
        $user = config('restify.auth.user_model')::query()->findOrFail($id);

        if ($user instanceof Sanctumable && ! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException('Invalid hash');
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return rest($user);
    }
}
