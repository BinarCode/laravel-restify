<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

class VisibleAttributesUser extends User
{
    protected $table = 'users';

    protected $hidden = [];

    protected $visible = ['id', 'name', 'email'];
}
