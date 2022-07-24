<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class UserLogout
{
    use SerializesModels;

    /**
     * @var Authenticatable
     */
    public $user;

    /**
     * @param  Authenticatable  $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
