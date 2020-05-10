<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class IndexControllerTest extends IntegrationTest
{
    public function test_list_repository()
    {
        factory(User::class)->create();
        factory(User::class)->create();
        factory(User::class)->create();

        $response = $this->withExceptionHandling()
            ->getJson('/restify-api/users');

        $response->assertJsonCount(3, 'data');
    }

    public function test_the_rest_controller_can_paginate()
    {
        $this->mockUsers(20);

        $class = (new class extends RestController {
            public function users()
            {
                return $this->response($this->search(User::class));
            }
        });

        $response = $class->search(User::class, [
            'match' => [
                'id' => 1,
            ],
        ]);
        $this->assertIsArray($class->search(User::class));
        $this->assertCount(1, $response['data']);
        $this->assertEquals(count($class->users()->getData()->data), User::$defaultPerPage);
    }
}
