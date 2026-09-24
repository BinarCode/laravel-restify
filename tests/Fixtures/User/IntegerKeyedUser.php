<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

/**
 * Declares its key type with the long 'integer' spelling Eloquent also accepts.
 */
class IntegerKeyedUser extends User
{
    protected $table = 'users';

    protected $keyType = 'integer';
}
