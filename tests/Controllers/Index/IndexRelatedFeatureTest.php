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
use PHPUnit\Framework\Attributes\Test;

class IndexRelatedFeatureTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singletonIf(RelatedDto::class, fn ($app) => new RelatedDto);
    }

    public function test_can_retrieve_nested_relationships(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'owner',
                'users' => HasMany::make('users', UserRepository::class),
                'extraData' => fn () => ['country' => 'Romania'],
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

        $this->withoutExceptionHandling()->getJson(CompanyRepository::route(query: [
            'related' => 'users.companies.users, users.posts, users.roles, extraData, extraMeta, owner',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
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
                ->missing('data.2.relationships.parent.relationships.parent')
                ->missing('data.2.relationships.parent.relationships.children')
                ->has('data.2.relationships.children')
                ->count('data.2.relationships.children', 2)
                ->missing('data.2.relationships.children.0.relationships.parent')
                ->missing('data.2.relationships.children.1.relationships.parent')
                ->where('data.0.attributes.comment', 'Children comments')
                ->has('data.0.relationships.parent')
                ->has('data.0.relationships.children')
                ->count('data.0.relationships.children', 0)
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
                ->has('data.0.relationships.post.relationships.user')
                ->where('data.1.id', '1')
                ->has('data.1.relationships.user')
                ->has('data.1.relationships.post')
                ->has('data.1.relationships.post.relationships.user')
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
                ->where('data.0.relationships.user', 'foo')
                ->etc()
        );
    }

    #[Test]
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

    #[Test]
    public function it_will_call_fields_method_for_related(): void
    {
        UserRepository::partialMock()
            ->shouldReceive('fields')
            ->once()
            ->andReturn([
                field('name'),
            ]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostFactory::one();

        $this->getJson(PostRepository::route(query: [
            'related' => 'user',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data.0.relationships.user.attributes.name')
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
