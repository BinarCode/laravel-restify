<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepositoryIndexControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_repository_per_page()
    {
        factory(Post::class, 20)->create();

        PostRepository::$defaultPerPage = 5;

        $response = $this->getJson('restify-api/posts');

        $this->assertCount(5, $response->json('data'));

        $response = $this->getJson('restify-api/posts?perPage=10');

        $this->assertCount(10, $response->json('data'));
    }

    public function test_repository_search_query_works()
    {
        factory(Post::class)->create([
            'title' => 'Title with code word',
        ]);

        factory(Post::class)->create([
            'title' => 'Another title with code inner',
        ]);

        factory(Post::class)->create([
            'title' => 'A title with no key word',
        ]);

        factory(Post::class)->create([
            'title' => 'Lorem ipsum dolor',
        ]);

        PostRepository::$search = ['title'];

        $response = $this->getJson('restify-api/posts?search=code');

        $this->assertCount(2, $response->json('data'));
    }

    public function test_repository_filter_works()
    {
        PostRepository::$match = [
            'title' => RestifySearchable::MATCH_TEXT,
        ];

        factory(Post::class)->create([
            'title' => 'Some title',
        ]);

        factory(Post::class)->create([
            'title' => 'Another one',
        ]);

        $response = $this
            ->getJson('restify-api/posts?title=Another one')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_repository_order()
    {
        PostRepository::$sort = [
            'title',
        ];

        factory(Post::class)->create(['title' => 'aaa']);

        factory(Post::class)->create(['title' => 'zzz']);

        $response = $this
            ->getJson('restify-api/posts?sort=-title')
            ->assertStatus(200);

        $this->assertEquals('zzz', $response->json('data.0.attributes.title'));
        $this->assertEquals('aaa', $response->json('data.1.attributes.title'));

        $response = $this
            ->getJson('restify-api/posts?order=-title')
            ->assertStatus(200);

        $this->assertEquals('zzz', $response->json('data.1.attributes.title'));
        $this->assertEquals('aaa', $response->json('data.0.attributes.title'));
    }

    public function test_repository_with_relations()
    {
        PostRepository::$related = ['user'];

        $user = $this->mockUsers(1)->first();

        factory(Post::class)->create(['user_id' => $user->id]);

        $response = $this->getJson('/restify-api/posts?related=user')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data.0.relationships.user'));
        $this->assertArrayNotHasKey('user', $response->json('data.0.attributes'));
    }

    public function test_index_unmergeable_repository_containes_only_explicitly_defined_fields()
    {
        factory(Post::class)->create();

        $response = $this->get('/restify-api/posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'attributes' => [
                        'user_id',
                        'title',
                        'description',
                    ],
                ]],
            ]);

        $this->assertArrayNotHasKey('image', $response->json('data.0.attributes'));
    }

    public function test_index_mergeable_repository_containes_model_attributes_and_local_fields()
    {
        factory(Post::class)->create();

        $this->get('/restify-api/posts-mergeable')
            ->assertJsonStructure([
                'data' => [[
                    'attributes' => [
                        'user_id',
                        'title',
                        'description',
                        'image',
                    ],
                ]],
            ]);
    }

    public function test_can_add_custom_index_main_meta_attributes()
    {
        factory(Post::class)->create([
            'title' => 'Post Title',
        ]);

        $response = $this->get('/restify-api/posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'postKey',
                ],
            ]);

        $this->assertEquals('Custom Meta Value', $response->json('meta.postKey'));
        $this->assertEquals('Post Title', $response->json('meta.first_title'));
    }
}
