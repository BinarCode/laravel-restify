<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Admin;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A second authenticatable of the application, signed in through its own guard and stored in its own table. Its
 * keys start at 1 like the keys of the users table, so an admin and a user regularly share an id.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar
 */
class Admin extends Authenticatable
{
    use HasApiTokens;

    public const GUARD = 'admin';

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
