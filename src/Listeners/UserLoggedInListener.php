<?php

namespace Binaryk\LaravelRestify\Listeners;

use Binaryk\LaravelRestify\Events\UserLoggedIn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserLoggedInListener
{
    /**
     * @param UserLoggedIn $event
     */
    public function handle(UserLoggedIn $event)
    {
        /** @var string $ipAddress */
        $ipAddress = $event->request->getClientIp();

        /** @var Model $authenticated */
        $authenticated = $event->authenticated;

        if ($authenticated->isFillable('last_login_at')) {
            $authenticated->last_login_at = Carbon::now();
        }

        if ($authenticated->isFillable('last_login_ip')) {
            $authenticated->last_login_ip = $ipAddress;
        }

        $authenticated->save();
    }
}
