<?php

namespace App\Restify;

use App\User;

class UserRepository extends \Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository
{
    public static string $model = User::class;
}
