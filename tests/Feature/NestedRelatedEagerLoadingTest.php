<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;

class NestedRelatedEagerLoadingTest extends IntegrationTestCase
{
    protected array $originalRelated;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRelated = UserRepository::$related;

        $this->authenticate();
    }

    protected function tearDown(): void
    {
        UserRepository::$related = $this->originalRelated;

        parent::tearDown();
    }

    protected function seedTree(int $users = 5): void
    {
        User::factory()->count($users)->create()->each(function (User $user) {
            Post::factory()->count(2)->create(['user_id' => $user->id])
                ->each(fn (Post $post) => Comment::factory()->count(2)->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]));
        });
    }

    protected function queriesMatching(string $needle): array
    {
        return collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $q) => str_contains($q, $needle))
            ->values()
            ->all();
    }

    public function test_single_level_related_is_eager_loaded(): void
    {
        $this->seedTree();

        DB::enableQueryLog();

        $this->getJson(UserRepository::route(query: ['related' => 'posts']))->assertOk();

        $postQueries = $this->queriesMatching('from "posts"');

        DB::disableQueryLog();

        $this->assertCount(
            1,
            $postQueries,
            'Expected posts to be eager loaded in a single query, got: '.json_encode($postQueries, JSON_PRETTY_PRINT)
        );
    }

    public function test_nested_related_requested_via_query_string_is_eager_loaded(): void
    {
        $this->seedTree();

        DB::enableQueryLog();

        $this->getJson(UserRepository::route(query: ['related' => 'posts.comments']))->assertOk();

        $postQueries = $this->queriesMatching('from "posts"');
        $commentQueries = $this->queriesMatching('from "comments"');

        DB::disableQueryLog();

        $this->assertCount(1, $postQueries, 'posts should be eager loaded once');
        $this->assertCount(
            1,
            $commentQueries,
            'Expected comments to be eager loaded in a single query, got '.count($commentQueries).': '
                .json_encode($commentQueries, JSON_PRETTY_PRINT)
        );
    }

    public function test_nested_related_declared_with_dot_notation_is_eager_loaded(): void
    {
        UserRepository::$related = ['posts.comments'];

        $this->seedTree();

        DB::enableQueryLog();

        $this->getJson(UserRepository::route(query: ['related' => 'posts.comments']))->assertOk();

        $postQueries = $this->queriesMatching('from "posts"');
        $commentQueries = $this->queriesMatching('from "comments"');

        DB::disableQueryLog();

        $this->assertCount(
            1,
            $postQueries,
            'Expected posts to be eager loaded once, got '.count($postQueries).': '
                .json_encode($postQueries, JSON_PRETTY_PRINT)
        );
        $this->assertCount(
            1,
            $commentQueries,
            'Expected comments to be eager loaded once, got '.count($commentQueries).': '
                .json_encode($commentQueries, JSON_PRETTY_PRINT)
        );
    }
}
