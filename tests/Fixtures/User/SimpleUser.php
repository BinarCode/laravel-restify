<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Models\LaravelRestifyModel;
use Illuminate\Database\Query\Builder;
use Mockery;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class SimpleUser extends LaravelRestifyModel
{
    protected $guarded = [];

    public function getEmail()
    {
        return $this->email;
    }

    public function createToken($name, array $scopes = [])
    {
        return new class {
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
}
