<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mockery;

/**
 * @property string email
 * @property string avatar
 * @property string avatar_size
 * @property string avatar_original
 */
class User extends Authenticatable implements Sanctumable, MustVerifyEmail
{
    use \Illuminate\Auth\MustVerifyEmail;
    use Notifiable;

    public static $search = ['id', 'email'];

    public static $sort = ['id'];

    public static $match = ['id' => 'int', 'email' => 'string'];

    public static $withs = ['posts'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'email_verified_at', 'avatar', 'created_at',
        'avatar_size',
        'avatar_original',
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

    public function post()
    {
        return $this->hasOne(Post::class);
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id')->withPivot([
            'is_admin',
        ])->withTimestamps();
    }

    /**
     * Set default test values.
     */
    public static function reset()
    {
        static::$search = ['id', 'email'];
        static::$sort = ['id'];
        static::$match = ['id' => 'int', 'email' => 'string'];
        static::$withs = ['posts'];
    }

    public function profile()
    {
        return [
            'roles' => [
                'admin',
            ],
        ];
    }
}
