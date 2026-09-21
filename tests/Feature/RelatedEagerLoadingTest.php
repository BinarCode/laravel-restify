<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatedEagerLoadingTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedThreeUsersWithPostsAndNestedComments();

        $this->recordQueries();
    }

    protected function tearDown(): void
    {
        UserRepository::$related = ['posts'];

        parent::tearDown();
    }

    /**
     * @param  list<string>  $declared
     */
    #[Test]
    #[TestWith([['posts'], 'posts', 1, 0], 'one level')]
    #[TestWith([['posts'], 'posts.comments', 1, 1], 'requested deeper than declared')]
    #[TestWith([['posts.comments'], 'posts.comments', 1, 1], 'declared with dot notation')]
    #[TestWith([['posts', 'posts.comments'], 'posts.comments', 1, 1], 'root declared beside the dotted path')]
    #[TestWith([['posts.comments.children'], 'posts.comments.children', 1, 2], 'three levels')]
    #[TestWith([['posts.comments.children.children'], 'posts.comments.children.children', 1, 3], 'four levels')]
    public function a_declared_path_costs_one_query_per_level(
        array $declared,
        string $requested,
        int $postSelects,
        int $commentSelects,
    ): void {
        UserRepository::$related = $declared;

        $this->getJson(UserRepository::route(query: ['related' => $requested]))->assertOk();

        $this->assertSelectCount($postSelects, Post::class);
        $this->assertSelectCount($commentSelects, Comment::class);
    }

    #[Test]
    public function every_level_of_a_three_level_path_reaches_the_response(): void
    {
        UserRepository::$related = ['posts.comments.children'];

        $this->getJson(UserRepository::route(query: ['related' => 'posts.comments.children']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Post of user 1'])
            ->assertJsonFragment(['comment' => 'Root comment on post of user 1'])
            ->assertJsonFragment(['comment' => 'Child comment on post of user 1']);
    }

    #[Test]
    public function a_sibling_of_a_nested_declaration_is_neither_loaded_nor_serialized(): void
    {
        UserRepository::$related = ['posts.comments'];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.user']))->assertOk();

        $this->assertSelectCount(0, Post::class);
        $this->assertSame([], array_keys($response->json('data.0.relationships') ?? []));
    }

    #[Test]
    public function a_mixed_request_loads_the_declared_path_and_drops_the_sibling(): void
    {
        UserRepository::$related = ['posts.comments'];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments,posts.user']))
            ->assertOk();

        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(1, Comment::class);
        $this->assertSame(['posts.comments'], array_keys($response->json('data.0.relationships')));
        $this->assertArrayNotHasKey('user', $this->firstDeclaredPost($response));
    }

    #[Test]
    public function a_dotted_key_on_an_eager_field_loads_the_field_relation(): void
    {
        UserRepository::$related = ['posts.comments' => HasMany::make('posts', PostRepository::class)];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments']))->assertOk();

        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(1, Comment::class);
        $this->assertSame(['posts.comments'], array_keys($response->json('data.0.relationships')));
    }

    #[Test]
    public function a_dotted_key_on_an_eager_field_does_not_constrain_its_siblings(): void
    {
        UserRepository::$related = ['posts.comments' => HasMany::make('posts', PostRepository::class)];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments,posts.user']))
            ->assertOk();

        $this->assertArrayHasKey('user', $this->firstDeclaredPost($response));
    }

    #[Test]
    public function a_path_whose_relation_does_not_exist_is_ignored(): void
    {
        UserRepository::$related = ['posts.comments'];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.nope']))->assertOk();

        $this->assertSelectCount(0, Post::class);
        $this->assertSame([], array_keys($response->json('data.0.relationships') ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    private function firstDeclaredPost(TestResponse $response): array
    {
        return $response->json('data.0.relationships')['posts.comments'][0];
    }

    private function seedThreeUsersWithPostsAndNestedComments(): void
    {
        foreach (User::factory(3)->create() as $index => $user) {
            $label = 'user '.($index + 1);

            foreach (Post::factory(2)->create(['user_id' => $user->id, 'title' => "Post of {$label}"]) as $post) {
                $root = Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'comment' => "Root comment on post of {$label}",
                ]);

                Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'parent_comment_id' => $root->id,
                    'comment' => "Child comment on post of {$label}",
                ]);
            }
        }
    }
}
