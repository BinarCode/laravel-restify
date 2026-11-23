<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Admin;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;

/**
 * An authenticatable that extends the users model to reuse its behaviour, and is stored in the admins table. It is
 * an instance of the users model without being a row of the users table.
 */
class StaffAdmin extends User
{
    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];
}
