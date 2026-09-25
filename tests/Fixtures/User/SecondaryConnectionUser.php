<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

class SecondaryConnectionUser extends User
{
    public const CONNECTION = 'restify_secondary';

    protected $connection = self::CONNECTION;

    protected $table = 'users';
}
