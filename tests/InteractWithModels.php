<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Tests\Fixtures\User;

/**
 * @package Binaryk\LaravelRestify\Tests;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithModels
{
    public function mockUsers($count = 1)
    {
        $users = collect([]);
        $i = 0;
        while($i < $count) {
            $users->push(factory(User::class)->create());
            $i++;
        }

        return $users;
    }

}
