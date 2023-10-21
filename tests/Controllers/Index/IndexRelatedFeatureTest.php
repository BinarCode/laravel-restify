<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Database\Factories\CommentFactory;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;

class IndexRelatedFeatureTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singletonIf(RelatedDto::class, fn ($app) => new RelatedDto());
    }

    public function test_can_retrieve_nested_relationships(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'owner',
                'users' => HasMany::make('users', UserRepository::class),
                'extraData' => fn () => ['country' => 'Romania'],
                'extraMeta' => new InvokableExtraMeta(),
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

        $this->withoutExceptionHandling()->getJson(CompanyRepository::route(query: [
            'related' => 'users.companies.users, users.posts, users.roles, extraData, extraMeta, owner',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.type', 'companies')
                ->has('data.0.relationships')
                ->has('data.0.relationships.users')
                ->where('data.0.relationships.users.data.0.type', 'users')
                ->has('included.1.relationships.posts')
                ->where('included.1.relationships.posts.data.0.type', 'posts')
                ->where('included.1.relationships.roles.data.0.type', 'roles')
                ->where('included.1.relationships.companies.data.0.type', 'companies')
                ->where('included.2', ['country' => 'Romania'])
                ->where('included.0.email', 'owner@owner.com')
                ->etc()
        );
    }

    public function test_can_load_nested_parent_from_the_same_table(): void
    {
        CommentRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('parent', CommentRepository::class),
                HasMany::make('children', CommentRepository::class),
            ]);

        $comment = Comment::factory()
            ->state(['comment' => 'Root comment'])
            ->for(Comment::factory()->state([
                'comment' => 'Parent comment',
            ]), 'parent')
            ->has(Comment::factory()->state(['comment' => 'Children comments'])->count(2), 'children')
            ->create();

        $this->assertCount(2, $comment->children()->get());
        $this->assertModelExists($comment->parent()->first());

        $this->withoutExceptionHandling();

        $this->getJson(CommentRepository::route(query: [
            'related' => 'parent, children',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.2.attributes.comment', 'Root comment')
                ->has('data.2.relationships.parent')
                ->missing('data.2.relationships.parent.data.relationships.parent')
                ->missing('data.2.relationships.parent.data.relationships.children')
                ->has('data.2.relationships.children.data')
                ->count('data.2.relationships.children.data', 2)
                ->missing('included.2.relationships.parent')
                ->missing('included.3.relationships.parent')
                ->where('data.0.attributes.comment', 'Children comments')
                ->has('data.0.relationships.parent.data')
                ->has('data.0.relationships.children.data')
                ->count('data.0.relationships.children.data', 0)
                ->etc()
        );
    }

    public function test_index_related_doesnt_load_for_nested_relationships_that_didnt_require_it(): void
    {
        CommentRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('user'),
                BelongsTo::make('post'),
            ]);

        PostRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                BelongsTo::make('user'),
            ]);

        CommentFactory::many();

        $this->getJson(CommentRepository::route(query: [
            'related' => 'user, post.user',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.id', '2')
                ->has('data.0.relationships.user')
                ->has('data.0.relationships.post')
                ->where('included.2.type', 'users')
                ->where('data.1.id', '1')
                ->has('data.1.relationships.user')
                ->has('data.1.relationships.post')
                ->where('included.3.type', 'users')
                ->etc()
        );

        app(RelatedDto::class)->reset();

        $this->getJson(CommentRepository::route(query: [
            'related' => 'user, post',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data.0.relationships.user')
                ->has('data.0.relationships.post')
                ->missing('data.0.relationships.post.relationships.user')
                ->has('data.1.relationships.user')
                ->has('data.1.relationships.post')
                ->missing('data.1.relationships.post.relationships.user')
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

        $this->getJson(PostRepository::route(query: [
            'related' => 'user',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('included.0', 'foo')
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

        $this->getJson(PostRepository::route(query: [
            'perPage' => 5,
            'related' => 'user',
            'sort' => 'id',
            'page' => 2,
        ]))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 1)
                    ->where('data.0.relationships.user.name', $owner)
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
