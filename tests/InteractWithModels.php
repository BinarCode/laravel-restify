<?php

namespace Binaryk\LaravelRestify\Tests;

use Binaryk\LaravelRestify\Tests\Fixtures\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithModels
{
    /**
     * @param  int  $count
     * @param  array  $predefinedEmails
     * @return \Illuminate\Support\Collection
     */
    public function mockUsers($count = 1, $predefinedEmails = [])
    {
        $users = collect([]);
        $i = 0;
        while ($i < $count) {
            $users->push(factory(User::class)->create());
            $i++;
        }

        foreach ($predefinedEmails as $email) {
            $users->push(factory(User::class)->create([
                'email' => $email,
            ]));
        }

        return $users->shuffle(); // randomly shuffles the items in the collection
    }

    /**
     * @param $userId
     * @param  int  $count
     * @return \Illuminate\Support\Collection
     */
    public function mockPosts($userId, $count = 1)
    {
        $users = collect([]);
        $i = 0;
        while ($i < $count) {
            $users->push(factory(Post::class)->create(
                ['user_id' => $userId]
            ));
            $i++;
        }

        return $users->shuffle(); // randomly shuffles the items in the collection
    }
}
