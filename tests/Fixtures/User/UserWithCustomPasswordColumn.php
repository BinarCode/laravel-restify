<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Contracts\Sanctumable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserWithCustomPasswordColumn extends Authenticatable implements Sanctumable
{
    protected $table = 'users';

    protected $guarded = [];

    public function getAuthPasswordName(): string
    {
        return 'name';
    }

    public function createToken($name, array $scopes = []): object
    {
        return new class
        {
            public string $plainTextToken = 'token';
        };
    }
}
