<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Apple;
use Binaryk\LaravelRestify\Tests\Fixtures\AppleRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepositoryIndexControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_repository_per_page()
    {
        factory(Apple::class, 20)->create();

        AppleRepository::$defaultPerPage = 5;

        $response = $this->getJson('restify-api/apples')
            ->assertStatus(200);

        $this->assertCount(5, $response->json('data'));

        $response = $this->getJson('restify-api/apples?perPage=10');

        $this->assertCount(10, $response->json('data'));
    }

    public function test_repository_search_query_works()
    {
        factory(Apple::class)->create([
            'title' => 'Some title',
        ]);

        factory(Apple::class)->create([
            'title' => 'Another one',
        ]);

        factory(Apple::class)->create([
            'title' => 'foo another',
        ]);

        factory(Apple::class)->create([
            'title' => 'Third apple',
        ]);

        AppleRepository::$search = ['title'];

        $response = $this->getJson('restify-api/apples?search=another')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_repository_filter_works()
    {
        AppleRepository::$match = [
            'title' => RestifySearchable::MATCH_TEXT,
        ];

        factory(Apple::class)->create([
            'title' => 'Some title',
        ]);

        factory(Apple::class)->create([
            'title' => 'Another one',
        ]);

        $response = $this
            ->getJson('restify-api/apples?title=Another one')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_repository_order()
    {
        AppleRepository::$sort = [
            'title',
        ];

        factory(Apple::class)->create(['title' => 'aaa']);

        factory(Apple::class)->create(['title' => 'zzz']);

        $response = $this
            ->getJson('restify-api/apples?sort=-title')
            ->assertStatus(200);

        $this->assertEquals('zzz', $response->json('data.0.attributes.title'));
        $this->assertEquals('aaa', $response->json('data.1.attributes.title'));

        $response = $this
            ->getJson('restify-api/apples?order=-title')
            ->assertStatus(200);

        $this->assertEquals('zzz', $response->json('data.1.attributes.title'));
        $this->assertEquals('aaa', $response->json('data.0.attributes.title'));
    }

    public function test_repsitory_with_relations()
    {
        AppleRepository::$related = ['user'];

        $user = $this->mockUsers(1)->first();

        factory(Apple::class)->create(['user_id' => $user->id]);

        $response = $this->getJson('/restify-api/apples?related=user')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data.0.relationships.user'));
        $this->assertArrayNotHasKey('user', $response->json('data.0.attributes'));
    }

    public function test_unmergeable_repository_containes_only_explicitly_defined_fields()
    {
        Restify::repositories([
            AppleTitleRepository::class,
        ]);

        factory(Apple::class)->create();

        $response = $this->get('/restify-api/apples-title')
            ->dump()
            ->assertStatus(200);

        $this->assertArrayHasKey('title', $response->json('data.0.attributes'));

        $this->assertArrayNotHasKey('id', $response->json('data.0.attributes'));
        $this->assertArrayNotHasKey('created_at', $response->json('data.0.attributes'));
    }

    public function test_mergeable_repository_containes_model_attributes_and_local_fields()
    {
        Restify::repositories([
            AppleMergeable::class,
        ]);

        factory(Apple::class)->create();

        $response = $this->get('/restify-api/apples-title')
            ->dump()
            ->assertStatus(200);

        $this->assertArrayHasKey('title', $response->json('data.0.attributes'));
        $this->assertArrayHasKey('id', $response->json('data.0.attributes'));
        $this->assertArrayHasKey('created_at', $response->json('data.0.attributes'));
    }
}

class AppleTitleRepository extends Repository {
    public static $uriKey = 'apples-title';

    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title'),
        ];
    }
}

class AppleMergeable extends Repository implements Mergeable {
    public static $uriKey = 'apples-title';

    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title'),
        ];
    }
}


