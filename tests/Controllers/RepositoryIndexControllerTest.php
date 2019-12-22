<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryIndexControllerTest extends IntegrationTest
{
    public function test_list_resource()
   {
        factory(User::class)->create();
        factory(User::class)->create();
        factory(User::class)->create();

        $response = $this->withExceptionHandling()
            ->getJson('/restify-api/users');

        $response->assertJsonCount(3, 'data.data');
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

    public function test_that_default_per_page_works()
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

    public function test_search_query_works()
    {
        $users = $this->mockUsers(10, ['eduard.lupacescu@binarcode.com']);
        $expected = $users->where('email', 'eduard.lupacescu@binarcode.com')->first()->serializeForIndex(request());
        $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'data' => [$expected],
                    'current_page' => 1,
                    'first_page_url' => "http://localhost/restify-api/users?page=1",
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => "http://localhost/restify-api/users?page=1",
                    'next_page_url' => null,
                    'path' => "http://localhost/restify-api/users",
                    'per_page' => 15,
                    'prev_page_url' => null,
                    'to' => 1,
                    'total' => 1,
                ],
                'errors' => [],
            ]);

        $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=some_unexpected_string_here')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'first_page_url' => "http://localhost/restify-api/users?page=1",
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => "http://localhost/restify-api/users?page=1",
                    'next_page_url' => null,
                    'path' => "http://localhost/restify-api/users",
                    'per_page' => 15,
                    'prev_page_url' => null,
                    'to' => 1,
                    'total' => 1,
                ],
                'errors' => [],
            ]);
    }

    public function test_that_desc_sort_query_param_works()
    {
        $this->mockUsers(10);
        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=-id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data['data'][0]['id'], 10);
        $this->assertSame($response->data['data'][9]['id'], 1);


    }

    public function test_that_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=+id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data['data'][0]['id'], 1);
        $this->assertSame($response->data['data'][9]['id'], 10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=id')//assert default ASC sort
        ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data['data'][0]['id'], 1);
        $this->assertSame($response->data['data'][9]['id'], 10);
    }

    public function test_that_default_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data['data'][0]['id'], 1);
        $this->assertSame($response->data['data'][9]['id'], 10);
    }

    public function test_that_match_param_works()
    {
        User::$match = ['email' => RestifySearchable::MATCH_TEXT]; // it will automatically filter over these queries (email='test@email.com')
        $users = $this->mockUsers(10, ['eduard.lupacescu@binarcode.com']);
        $expected = $users->where('email', 'eduard.lupacescu@binarcode.com')->first()->serializeForIndex(request());

        $this->withExceptionHandling()
            ->get('/restify-api/users?email=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'data' => [$expected],
                    'current_page' => 1,
                    'first_page_url' => "http://localhost/restify-api/users?page=1",
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => "http://localhost/restify-api/users?page=1",
                    'next_page_url' => null,
                    'path' => "http://localhost/restify-api/users",
                    'per_page' => 15,
                    'prev_page_url' => null,
                    'to' => 1,
                    'total' => 1,
                ],
                'errors' => [],
            ]);
    }

    public function test_that_with_param_works()
    {
        User::$match = ['email' => RestifySearchable::MATCH_TEXT]; // it will automatically filter over these queries (email='test@email.com')
        $users = $this->mockUsers(1);
        $posts = $this->mockPosts(1, 2);
        $expected = $users->first()->serializeForIndex(request());
        $expected['posts'] = $posts->toArray();
        $r = $this->withExceptionHandling()
            ->get('/restify-api/users?with=posts')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSameSize($r->data['data']->first()['posts'], $expected['posts']);
    }
}
