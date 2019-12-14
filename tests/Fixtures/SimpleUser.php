<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Contracts\Passportable;
use Binaryk\LaravelRestify\Models\LaravelRestifyModel;

/**
 * @package Binaryk\LaravelRestify\Tests\Fixtures;
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
}
