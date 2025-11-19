<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mockery;

/**
 * @property int id
 * @property string email
 * @property string avatar
 * @property string avatar_size
 * @property string avatar_original
 */
class User extends Authenticatable implements MustVerifyEmail, Sanctumable
{
    use HasFactory;
    use \Illuminate\Auth\MustVerifyEmail;
    use Notifiable;

    public static $search = ['id', 'email'];

    public static $sort = ['id'];

    public static $match = ['id' => 'int', 'email' => 'string'];

    public static $withs = ['posts'];

    protected $fillable = [
        'id',
        'name',
        'email',
        'active',
        'password',
        'creator_id',
        'email_verified_at',
        'avatar',
        'created_at',
        'avatar_size',
        'avatar_original',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getEmail(): string
    {
        return $this->email;
    }

    public function createToken($name, array $scopes = []): object
    {
        return new class
        {
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function post(): HasOne
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(static::class, 'creator_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
