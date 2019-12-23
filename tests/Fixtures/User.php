<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mockery;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class User extends Authenticatable implements Passportable, MustVerifyEmail, RestifySearchable
{
    use \Illuminate\Auth\MustVerifyEmail;
    use Notifiable,
        InteractWithSearch;

    public static $search = ['id', 'email'];
    public static $sort = ['id'];
    public static $match = ['id' => 'int', 'email' => 'string'];
    public static $in = ['id' => 'int'];
    public static $withs = ['posts'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'email_verified_at',
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

    /**
     * @return Builder
     */
    public function tokens()
    {
        return Mockery::mock(Builder::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Set default test values
     */
    public static function reset() {
        static::$search = ['id', 'email'];
        static::$sort = ['id'];
        static::$match = ['id' => 'int', 'email' => 'string'];
        static::$in = ['id' => 'int'];
        static::$withs = ['posts'];
    }
}
