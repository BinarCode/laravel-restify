<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ScopedPostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * An index getter reaches the rows the repository lists, and a show getter a model the repository shows.
 */
class GetterQueryScopeTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([ScopedPostRepository::class]);

        $this->authenticate();
    }

    #[Test]
    public function an_index_getter_reaches_only_the_rows_the_index_query_lists(): void
    {
        $first = Post::factory()->create();
        $unlisted = Post::factory()->create();
        $last = Post::factory()->create();

        ScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $listed = [$first->getKey(), $last->getKey()];

        $this->indexGetter()
            ->assertOk()
            ->assertExactJson(['filtered' => $listed, 'collected' => $listed]);
    }

    #[Test]
    public function an_index_getter_cannot_collect_an_excluded_row_by_its_id(): void
    {
        $listed = Post::factory()->create();
        $unlisted = Post::factory()->create();

        ScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $this->indexGetter(['repositories' => [$listed->getKey(), $unlisted->getKey()]])
            ->assertOk()
            ->assertJsonPath('collected', [$listed->getKey()]);
    }

    #[Test]
    public function an_index_getter_still_narrows_its_rows_with_the_request_filters(): void
    {
        $matching = Post::factory()->create(['title' => 'Matching']);
        $unlistedMatching = Post::factory()->create(['title' => 'Matching']);
        Post::factory()->create(['title' => 'Other']);

        ScopedPostRepository::$unlisted = [$unlistedMatching->getKey()];

        $this->indexGetter(['title' => 'Matching'])
            ->assertOk()
            ->assertExactJson(['filtered' => [$matching->getKey()], 'collected' => [$matching->getKey()]]);
    }

    #[Test]
    public function an_index_getter_reaches_every_row_when_the_index_query_excludes_none(): void
    {
        $keys = Post::factory()->count(3)->create()->modelKeys();

        $this->indexGetter()
            ->assertOk()
            ->assertExactJson(['filtered' => $keys, 'collected' => $keys]);
    }

    #[Test]
    public function the_index_query_scope_of_index_getters_can_be_turned_off(): void
    {
        config()->set('restify.repositories.apply_index_query_to_actions_and_getters', false);

        $listed = Post::factory()->create();
        $unlisted = Post::factory()->create();

        ScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $keys = [$listed->getKey(), $unlisted->getKey()];

        $this->indexGetter()
            ->assertOk()
            ->assertExactJson(['filtered' => $keys, 'collected' => $keys]);
    }

    #[Test]
    public function a_show_getter_on_a_model_the_show_query_excludes_is_not_found(): void
    {
        $shown = Post::factory()->create(['title' => 'Shown']);
        $notShown = Post::factory()->create(['title' => 'Not shown']);

        ScopedPostRepository::$unshowable = [$notShown->getKey()];

        $this->getJson(ScopedPostRepository::route($notShown))->assertNotFound();

        $this->showGetter($notShown)
            ->assertNotFound()
            ->assertDontSee('Not shown');

        $this->showGetter($shown)
            ->assertOk()
            ->assertExactJson(['title' => 'Shown']);
    }

    #[Test]
    public function a_show_getter_is_not_limited_by_the_index_query(): void
    {
        $unlisted = Post::factory()->create(['title' => 'Unlisted']);

        ScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $this->getJson(ScopedPostRepository::route($unlisted))->assertOk();

        $this->showGetter($unlisted)
            ->assertOk()
            ->assertExactJson(['title' => 'Unlisted']);
    }

    #[Test]
    public function the_show_query_scope_of_show_getters_can_be_turned_off(): void
    {
        config()->set('restify.repositories.apply_show_query_to_actions_and_getters', false);

        $notShown = Post::factory()->create(['title' => 'Not shown']);

        ScopedPostRepository::$unshowable = [$notShown->getKey()];

        $this->showGetter($notShown)
            ->assertOk()
            ->assertExactJson(['title' => 'Not shown']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function indexGetter(array $query = []): TestResponse
    {
        return $this->getJson(ScopedPostRepository::route('getters/reachable-posts', query: ['repositories' => 'all', ...$query]));
    }

    private function showGetter(Post $post): TestResponse
    {
        return $this->getJson(ScopedPostRepository::route("{$post->getKey()}/getters/post-title"));
    }
}
