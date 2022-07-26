<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;

class RepositoryIndexControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    /** * @test */
    public function it_can_paginate(): void
    {
        PostFactory::many(15);

        PostRepository::$defaultPerPage = 5;

        $this->getJson(PostRepository::route())
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->count('data', 5)
                    ->etc()
            );

        $this->getJson(PostRepository::route(null, [
            'perPage' => 10,
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->count('data', 10)
                ->etc()
        );

        $this->getJson(PostRepository::route(null, [
            'page[size]' => 10,
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->count('data', 10)
                ->etc()
        );

        $this->getJson(PostRepository::route(null, [
            'perPage' => 10,
            'page' => '2',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->count('data', 5)
                ->etc()
        );

        $this->getJson(PostRepository::route(null, [
            'page[size]' => 10,
            'page[number]' => 2,
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->count('data', 5)
                ->etc()
        );
    }

    /** * @test */
    public function it_can_search_using_query(): void
    {
        PostFactory::one([
            'title' => 'Title with code word',
        ]);

        PostFactory::one([
            'title' => 'Another title with code inner',
        ]);

        PostFactory::one([
            'title' => 'A title with no key word',
        ]);

        PostFactory::one([
            'title' => 'Lorem ipsum dolor',
        ]);

        PostRepository::$search = ['title'];

        $this->getJson(PostRepository::route(null, [
            'search' => 'code',
        ]))->assertJson(fn(AssertableJson $json) => $json->count('data', 2)->etc());
    }

    /** * @test */
    public function it_can_sort_using_query(): void
    {
        PostFactory::one([
            'title' => 'AAA',
        ]);

        PostFactory::one([
            'title' => 'ZZZ',
        ]);

        PostRepository::$sort = [
            'title',
        ];

        $this->getJson(PostRepository::route(null, [
            'sort' => '-title',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->where('data.0.attributes.title', 'ZZZ')
                ->where('data.1.attributes.title', 'AAA')
                ->etc()
        );

        $this->getJson(PostRepository::route(null, [
            'sort' => 'title',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->where('data.0.attributes.title', 'AAA')
                ->where('data.1.attributes.title', 'ZZZ')
                ->etc()
        );
    }

    /** * @test */
    public function it_can_return_related_entity(): void
    {
        PostRepository::$related = [
            'user',
        ];

        Post::factory()->for(
            User::factory()->state([
                'name' => $name = 'John Doe',
            ])
        )->create();

        $this->getJson(PostRepository::route(null, [
            'related' => 'user',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user.name', $name)
                ->etc()
        );
    }

    public function test_repository_can_resolve_related_using_callables(): void
    {
        PostRepository::$related = [
            'user' => function ($request, $repository) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Repository::class, $repository);

                return 'foo';
            },
        ];

        PostFactory::one();

        $this->getJson(PostRepository::route(null, [
            'related' => 'user',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user', 'foo')
                ->etc()
        );
    }

    public function test_can_retrieve_nested_relationships(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'owner',
                'users' => HasMany::make('users', UserRepository::class),
                'extraData' => fn() => ['country' => 'Romania'],
                'extraMeta' => new InvokableExtraMeta,
            ]);

        UserRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'posts' => HasMany::make('posts', PostRepository::class),
                'roles' => MorphToMany::make('roles', RoleRepository::class),
                'companies' => BelongsToMany::make('companies', CompanyRepository::class),
            ]);

        Company::factory()
            ->for(User::factory()->state([
                'email' => 'owner@owner.com',
            ]), 'owner')
            ->has(
                User::factory()->has(
                    Post::factory()->count(2)
                )->has(
                    Role::factory()
                )
            )->create();

        $this->withoutExceptionHandling()->getJson(CompanyRepository::route(null, [
            'related' => 'users.companies.users, users.posts, users.roles, extraData, extraMeta, owner',
        ]))->assertJson(
            fn(AssertableJson $json) => $json
                ->where('data.0.type', 'companies')
                ->has('data.0.relationships')
                ->has('data.0.relationships.users')
                ->where('data.0.relationships.users.0.type', 'users')
                ->has('data.0.relationships.users.0.relationships.posts')
                ->where('data.0.relationships.users.0.relationships.posts.0.type', 'posts')
                ->where('data.0.relationships.users.0.relationships.roles.0.type', 'roles')
                ->where('data.0.relationships.users.0.relationships.companies.0.type', 'companies')
                ->where('data.0.relationships.extraData', ['country' => 'Romania'])
                ->where('data.0.relationships.owner.email', 'owner@owner.com')
                ->etc()
        );
    }

    /** * @test */
    public function it_can_paginate_keeping_relationships(): void
    {
        PostRepository::$related = [
            'user',
        ];

        PostRepository::$sort = [
            'id',
        ];

        PostFactory::many(5);

        Post::factory()->for(User::factory()->state([
            'name' => $owner = 'John Doe',
        ]))->create();

        $this->getJson(PostRepository::route(null, [
            'perPage' => 5,
            'related' => 'user',
            'sort' => 'id',
            'page' => 2,
        ]))
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->count('data', 1)
                    ->where('data.0.relationships.user.name', $owner)
                    ->etc()
            );
    }

    public function test_index_unmergeable_repository_contains_only_explicitly_defined_fields(): void
    {
        PostFactory::one();

        $response = $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'attributes' => [
                            'user_id',
                            'title',
                            'description',
                        ],
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('image', $response->json('data.0.attributes'));
    }

    public function test_index_mergeable_repository_contains_model_attributes_and_local_fields(): void
    {
        Restify::repositories([
            PostMergeableRepository::class,
        ]);

        $this->getJson(PostMergeableRepository::route(
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
        Post::factory()->create([
            'title' => 'Post Title',
        ]);

        $response = $this->getJson(PostRepository::route())
            ->assertJsonStructure([
                'meta' => [
                    'postKey',
                ],
            ]);

        $this->assertEquals('Custom Meta Value', $response->json('meta.postKey'));
        $this->assertEquals('Post Title', $response->json('meta.first_title'));
    }

    public function test_can_load_nested_from_the_same_table(): void
    {
        UserRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('creator', UserRepository::class),
            ]);

        $company = Company::factory()->has(
            User::factory()->state(['email' => 'user@user.com'])->for(
                User::factory()->state(['email' => 'creator@creator.com']), 'creator'
            )->has(
                Post::factory()->count(2)
            )
        )->create();

        $userId = $company->users()->first()->id;

        $this->getJson(CompanyRepository::route(query: [
            'related' => 'users.creator',
        ]))->assertJson(fn(AssertableJson $json) => $json
            ->missing('data.0.relationships.users.0.relationships.creator.relationships.creator')
            ->where('data.0.relationships.users.0.relationships.creator.attributes.email', 'creator@creator.com')
            ->etc()
        );

        app(RelatedDto::class)->reset();

        $this->getJson(UserRepository::route(query: [
            'id' => $userId,
            'related' => 'creator',
        ]))->assertJson(fn(AssertableJson $json) => $json
            ->where('data.0.relationships.creator.attributes.email', 'creator@creator.com')
            ->etc()
        );
    }
}

class InvokableExtraMeta
{
    public function __invoke()
    {
        return [
            'userCount' => 10,
        ];
    }
}
