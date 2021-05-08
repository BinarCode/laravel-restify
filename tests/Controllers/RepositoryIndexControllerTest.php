<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\RelatedCastWithAttributes;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class RepositoryIndexControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_repository_per_page()
    {
        factory(Post::class, 20)->create();

        PostRepository::$defaultPerPage = 5;

        $response = $this->getJson('posts');

        $this->assertCount(5, $response->json('data'));

        $response = $this->getJson('posts?perPage=10');

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

        $response = $this->getJson('posts?search=code');

        $this->assertCount(2, $response->json('data'));
    }

    public function test_repository_order()
    {
        PostRepository::$sort = [
            'title',
        ];

        factory(Post::class)->create(['title' => 'aaa']);

        factory(Post::class)->create(['title' => 'zzz']);

        $response = $this->getJson('posts?sort=-title')
            ->assertOk();

        $this->assertEquals('zzz', $response->json('data.0.attributes.title'));
        $this->assertEquals('aaa', $response->json('data.1.attributes.title'));

        $response = $this->getJson('posts?sort=title')
            ->assertOk();

        $this->assertEquals('aaa', $response->json('data.0.attributes.title'));
        $this->assertEquals('zzz', $response->json('data.1.attributes.title'));
    }

    public function test_repository_with_relations()
    {
        PostRepository::$related = ['user'];

        $user = $this->mockUsers(1)->first();

        factory(Post::class)->create(['user_id' => $user->id]);

        $response = $this->getJson('posts?related=user')
            ->assertOk();

        $this->assertCount(1, $response->json('data.0.relationships.user'));
        $this->assertArrayNotHasKey('user', $response->json('data.0.attributes'));
    }

    public function test_repository_can_resolve_related_using_callables()
    {
        PostRepository::$related = [
            'user' => function ($request, $repository) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Repository::class, $repository);

                return 'foo';
            }
        ];

        $user = $this->mockUsers(1)->first();

        factory(Post::class)->create(['user_id' => $user->id]);

        $this->getJson('posts?related=user')
            ->assertJsonFragment([
                'user' => 'foo',
            ])
            ->assertOk();
    }

    public function test_using_custom_related_casts()
    {
        PostRepository::$related = ['user'];

        config([
            'restify.casts.related' => RelatedCastWithAttributes::class,
        ]);

        $user = $this->mockUsers(1)->first();

        factory(Post::class)->create(['user_id' => $user->id]);

        $this->getJson('posts?related=user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'relationships' => [
                            'user' => [
                                ['attributes'],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_repository_with_nested_relations()
    {
        CompanyRepository::partialMock()
            ->expects('related')
            ->andReturn([
                'users.posts',
            ]);

        tap(factory(Company::class)->create(), function (Company $company) {
            tap($company->users()->create(
                array_merge(factory(User::class)->make()->toArray(), [
                    'password' => 'secret',
                ])
            ), function (User $user) {
                factory(Post::class)->create(['user_id' => $user->id]);
            });
        });

        $response = $this->getJson(CompanyRepository::uriKey().'?related=users.posts')
            ->assertOk();

        $this->assertCount(1, $response->json('data.0.relationships')['users.posts']);
        $this->assertCount(1, $response->json('data.0.relationships')['users.posts'][0]['posts']);
    }

    public function test_paginated_repository_with_relations()
    {
        PostRepository::$related = ['user'];

        $user = $this->mockUsers(1)->first();

        factory(Post::class, 20)->create(['user_id' => $user->id]);

        $response = $this->getJson('posts?related=user&page=2')
            ->assertOk();

        $this->assertCount(1, $response->json('data.0.relationships.user'));
        $this->assertArrayNotHasKey('user', $response->json('data.0.attributes'));
    }

    public function test_index_unmergeable_repository_contains_only_explicitly_defined_fields(): void
    {
        factory(Post::class)->create();

        $response = $this->get('posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'attributes' => [
                            'user_id',
                            'title',
                            'description',
                        ],
                    ]
                ],
            ]);

        $this->assertArrayNotHasKey('image', $response->json('data.0.attributes'));
    }

    public function test_index_mergeable_repository_contains_model_attributes_and_local_fields(): void
    {
        Restify::repositories([
            PostMergeableRepository::class,
        ]);

        $this->get(PostMergeableRepository::to(
            $this->mockPost()->id
        ))->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'user_id',
                    'title',
                    'description',
                    'image',
                ],
            ],
        ]);
    }

    public function test_can_add_custom_index_main_meta_attributes(): void
    {
        factory(Post::class)->create([
            'title' => 'Post Title',
        ]);

        $response = $this->get('posts')
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
