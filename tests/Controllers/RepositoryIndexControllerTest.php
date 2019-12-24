<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Mockery;

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

        $response->assertJsonCount(3, 'data');
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
        $request  = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('isResolvedByRestify')
            ->andReturnFalse();
        $expected = $users->where('email', 'eduard.lupacescu@binarcode.com')->first()->serializeForIndex($request);
        $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJson([
                'links' => [
                    'last_page_url' => 'http://localhost/restify-api/users?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost/restify-api/users',
                    'first_page_url' => 'http://localhost/restify-api/users?page=1',
                    'prev_page_url' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'to' => 1,
                    'total' => 1,
                ],
                'data' => [$expected],
            ]);

        $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=some_unexpected_string_here')
            ->assertStatus(200)
            ->assertJson([
                'links' => [
                    'last_page_url' => 'http://localhost/restify-api/users?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost/restify-api/users',
                    'first_page_url' => 'http://localhost/restify-api/users?page=1',
                    'prev_page_url' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'to' => 1,
                    'total' => 1,
                ],
                'data' => []
            ]);
    }

    public function test_that_desc_sort_query_param_works()
    {
        $this->mockUsers(10);
        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=-id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data[0]['attributes']['id'], 10);
        $this->assertSame($response->data[9]['attributes']['id'], 1);
    }

    public function test_that_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=+id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data[0]['attributes']['id'], 1);
        $this->assertSame($response->data[9]['attributes']['id'], 10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=id')//assert default ASC sort
        ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data[0]['attributes']['id'], 1);
        $this->assertSame($response->data[9]['attributes']['id'], 10);
    }

    public function test_that_default_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->withExceptionHandling()->get('/restify-api/users?sort=id')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSame($response->data[0]['attributes']['id'], 1);
        $this->assertSame($response->data[9]['attributes']['id'], 10);
    }

    public function test_that_match_param_works()
    {
        User::$match = ['email' => RestifySearchable::MATCH_TEXT]; // it will automatically filter over these queries (email='test@email.com')
        $users = $this->mockUsers(10, ['eduard.lupacescu@binarcode.com']);
        $request  = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('isResolvedByRestify')
            ->andReturnFalse();

        $expected = $users->where('email', 'eduard.lupacescu@binarcode.com')->first()->serializeForIndex($request);

        $this->withExceptionHandling()
            ->get('/restify-api/users?email=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJson([
                'links' => [
                    'last_page_url' => 'http://localhost/restify-api/users?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost/restify-api/users',
                    'first_page_url' => 'http://localhost/restify-api/users?page=1',
                    'prev_page_url' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'to' => 1,
                    'total' => 1,
                ],
                'data' => [$expected],
            ]);
    }

    public function test_that_with_param_works()
    {
        User::$match = ['email' => RestifySearchable::MATCH_TEXT]; // it will automatically filter over these queries (email='test@email.com')
        $users = $this->mockUsers(1);
        $posts = $this->mockPosts(1, 2);
        $request  = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('isResolvedByRestify')
            ->andReturnFalse();
        $expected = $users->first()->serializeForIndex($request);
        $expected['posts'] = $posts->toArray();
        $r = $this->withExceptionHandling()
            ->get('/restify-api/users?with=posts')
            ->assertStatus(200)
            ->getOriginalContent();

        $this->assertSameSize($r->data[0]['attributes']['posts'], $expected['posts']);
    }
}
