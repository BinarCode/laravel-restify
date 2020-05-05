<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Mockery;

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

    public function test_that_default_per_page_works()
    {
        User::$defaultPerPage = 40;
        $this->mockUsers(50);

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
        $this->assertEquals(count($class->users()->getData()->data), 40);
        User::$defaultPerPage = RestifySearchable::DEFAULT_PER_PAGE;
    }

    public function test_search_query_works()
    {
        $users = $this->mockUsers(10, ['eduard.lupacescu@binarcode.com']);
        $model = $users->where('email', 'eduard.lupacescu@binarcode.com')->first(); //find manually the model
        $repository = Restify::repositoryForModel(get_class($model));
        $expected = $repository::resolveWith($model)->toArray(resolve(RestifyRequest::class));
        unset($expected['relationships']);

        $r = $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJsonStructure([
                'links' => [
                    'last',
                    'next',
                    'first',
                    'prev',
                ],
                'meta' => [
                    'path',
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
                'data',
            ])->decodeResponseJson();

        $this->assertCount(1, $r['data']);

        $this->withExceptionHandling()
            ->getJson('/restify-api/users?search=some_unexpected_string_here')
            ->assertStatus(200)
            ->assertJson([
                'links' => [
                    'next' => null,
                    'last' => 'http://localhost/restify-api/users?page=1',
                    'first' => 'http://localhost/restify-api/users?page=1',
                    'prev' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => null,
                    'last_page' => 1,
                    'per_page' => 15,
                    'to' => null,
                    'path' => 'http://localhost/restify-api/users',
                    'total' => 0,
                ],
                'data' => [],
            ]);
    }

    public function test_that_desc_sort_query_param_works()
    {
        $this->mockUsers(10);
        $response = $this->getJson('/restify-api/users?sort=-id')
            ->assertStatus(200);

        $this->assertSame($response->json('data.0.attributes.id'), 10);
        $this->assertSame($response->json('data.9.attributes.id'), 1);
    }

    public function test_that_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->getJson('/restify-api/users?sort=+id')
            ->assertStatus(200);

        $this->assertSame($response->json('data.0.id'), 1);
        $this->assertSame($response->json('data.9.id'), 10);
    }

    public function test_that_default_asc_sort_query_param_works()
    {
        $this->mockUsers(10);

        $response = $this->get('/restify-api/users?sort=id')->assertStatus(200);

        $this->assertSame($response->json('data.0.id'), 1);
        $this->assertSame($response->json('data.9.id'), 10);
    }

    public function test_that_match_param_works()
    {
        User::$match = ['email' => RestifySearchable::MATCH_TEXT]; // it will automatically filter over these queries (email='test@email.com')
        $users = $this->mockUsers(10, ['eduard.lupacescu@binarcode.com']);
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('has')
            ->andReturnFalse();
        $request->shouldReceive('get')
            ->andReturnFalse();
        $request->shouldReceive('isDetailRequest')
            ->andReturnFalse();
        $request->shouldReceive('isIndexRequest')
            ->andReturnTrue();

        $model = $users->where('email', 'eduard.lupacescu@binarcode.com')->first();
        $repository = Restify::repositoryForModel(get_class($model));
        $expected = $repository::resolveWith($model)->toArray($request);

        unset($expected['relationships']);
        $this->withExceptionHandling()
            ->get('/restify-api/users?email=eduard.lupacescu@binarcode.com')
            ->assertStatus(200)
            ->assertJson([
                'links' => [
                    'last' => 'http://localhost/restify-api/users?page=1',
                    'next' => null,
                    'first' => 'http://localhost/restify-api/users?page=1',
                    'prev' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'path' => 'http://localhost/restify-api/users',
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
        $this->mockUsers(1);
        $posts = $this->mockPosts(1, 2);
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('has')
            ->andReturnTrue();
        $request->shouldReceive('get')
            ->andReturn('posts');
        $request->shouldReceive('isDetailRequest')
            ->andReturnFalse();
        $request->shouldReceive('isIndexRequest')
            ->andReturnTrue();

        $r = $this->getJson('/restify-api/users?with=posts')
            ->assertStatus(200);

        $this->assertSameSize((array) data_get($r, 'data.0.relationships.posts'), $posts->toArray());
        $this->assertSame(array_keys((array) data_get($r, 'data.0.relationships.posts.0')), [
            'id', 'type', 'attributes', 'meta',
        ]);
    }
}
