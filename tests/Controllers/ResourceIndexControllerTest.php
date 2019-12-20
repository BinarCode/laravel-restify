<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResourceIndexControllerTest extends IntegrationTest
{
    public function test_list_resource()
    {
        factory(User::class)->create();
        factory(User::class)->create();
        $user = factory(User::class)->create();

        $response = $this->withExceptionHandling()
            ->getJson('/restify-api/users');

        dd($response);
    }
}
