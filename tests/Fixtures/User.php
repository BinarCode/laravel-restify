<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class User extends Authenticatable implements Passportable, MustVerifyEmail
{
    use \Illuminate\Auth\MustVerifyEmail;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getEmail()
    {
        return $this->email;
    }

    public function createToken($name, array $scopes = [])
    {
        return new class {
            public $accessToken = 'token';
        };
    }
}
