<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Illuminate\Database\Eloquent\SoftDeletes;

class TrashableUser extends User
{
    use SoftDeletes;

    protected $table = 'users';
}
