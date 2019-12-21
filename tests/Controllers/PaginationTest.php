<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

/**
 * @package Binaryk\LaravelRestify\Tests\Controllers;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class PaginationTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_the_rest_controller_can_paginate()
    {
        $this->mockUsers(50);

        $class = (new class extends RestController {
            public function users()
            {
                return $this->respond($this->search(User::class));
            }
        });

        $response = $class->search(User::class, [
            'match' => [
                'id' => 1,
            ],
        ]);
        $this->assertIsArray($class->search(User::class));
        $this->assertCount(1, $response['data']);
        $this->assertEquals(count($class->users()->getData()->data->data), User::$defaultPerPage);
    }

    public function test_per_page()
    {
        User::$defaultPerPage = 40;
        $this->mockUsers(50);

        $class = (new class extends RestController {
            public function users()
            {
                return $this->respond($this->search(User::class));
            }
        });

        $response = $class->search(User::class, [
            'match' => [
                'id' => 1,
            ],
        ]);
        $this->assertIsArray($class->search(User::class));
        $this->assertCount(1, $response['data']);
        $this->assertEquals(count($class->users()->getData()->data->data), 40);
        User::$defaultPerPage = RestifySearchable::DEFAULT_PER_PAGE;
    }
}
