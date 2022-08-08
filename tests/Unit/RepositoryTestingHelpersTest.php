<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryTestingHelpersTest extends IntegrationTest
{
    public function test_action_helper_accepts_key(): void
    {
        $path = PostRepository::action(
            PublishPostAction::class,
            1
        );

        $this->assertSame('/api/restify/posts/1/actions?action=publish-post-action', $path);

        $path = PostRepository::action(
            PublishPostAction::class
        );

        $this->assertSame('/api/restify/posts/actions?action=publish-post-action', $path);
    }

    public function test_route_helper_accepts_model(): void
    {
        $post = PostFactory::one();

        $path = PostRepository::route(
            $post
        );

        $this->assertSame('/api/restify/posts/1', $path);
    }

    public function test_route_helper_accepts_query(): void
    {
        $path = PostRepository::route(
            query: ['search' => 'Foo', 'related' => 'owner']
        );

        $this->assertSame('/api/restify/posts?search=Foo&related=owner', $path);
    }

    public function test_route_helper_accepts_action(): void
    {
        $path = PostRepository::route(
            query: ['related' => 'owner'],
            action: app(PublishPostAction::class)
        );

        $this->assertSame('/api/restify/posts/actions?related=owner&action=publish-post-action', $path);
    }

    public function test_getter_helper_accepts_getter(): void
    {
        $path = PostRepository::getter(
            PostsShowGetter::class,
        );

        $this->assertSame('/api/restify/posts/getters/posts-show-getter', $path);
    }
}
