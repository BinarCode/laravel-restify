<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SanctumUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $guarded = [];
}
