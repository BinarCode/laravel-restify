<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Models\LaravelRestifyModel;
use Illuminate\Database\Query\Builder;
use Mockery;

class SampleUser extends LaravelRestifyModel
{
    protected $guarded = [];

    public function getEmail()
    {
        return $this->email;
    }

    public function createToken($name, array $scopes = [])
    {
        return new class
        {
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
