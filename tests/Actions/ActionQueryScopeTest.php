<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ScopedPostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * An index action runs on the rows the repository lists, and a show action on a model the repository shows.
 */
class ActionQueryScopeTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([ScopedPostRepository::class]);

        $this->authenticate();
    }

    #[Test]
    public function an_index_action_for_all_skips_the_rows_the_index_query_excludes(): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        $this->indexAction('activate-posts', 'all')
            ->assertOk()
            ->assertExactJson(['activated' => $listed]);

        $this->assertActive($listed);
        $this->assertInactive($unlisted);
    }

    #[Test]
    #[TestWith([false], 'integer ids')]
    #[TestWith([true], 'string ids')]
    public function an_index_action_for_explicit_ids_skips_the_rows_the_index_query_excludes(bool $idsAsStrings): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        $ids = [...$listed, ...$unlisted];

        $this->indexAction('activate-posts', $idsAsStrings ? array_map('strval', $ids) : $ids)
            ->assertOk()
            ->assertExactJson(['activated' => $listed]);

        $this->assertActive($listed);
        $this->assertInactive($unlisted);
    }

    #[Test]
    public function an_index_action_for_only_excluded_ids_is_not_found(): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        $this->indexAction('activate-posts', $unlisted)->assertNotFound();

        $this->assertInactive([...$listed, ...$unlisted]);
    }

    #[Test]
    public function an_index_action_the_user_may_not_run_does_not_succeed_when_every_id_is_excluded(): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        ScopedPostRepository::$canRun = false;

        $this->indexAction('activate-posts-with-can-run', $unlisted)->assertNotFound();
        $this->indexAction('activate-posts-with-can-run', $listed)->assertForbidden();

        $this->assertInactive([...$listed, ...$unlisted]);
    }

    #[Test]
    public function an_index_action_for_ids_that_do_not_exist_is_not_found(): void
    {
        [$listed] = $this->listedAndUnlistedPosts();

        $this->indexAction('activate-posts', [max($listed) + 100])->assertNotFound();

        $this->assertInactive($listed);
    }

    #[Test]
    public function an_index_action_for_all_runs_on_no_models_when_the_index_query_lists_none(): void
    {
        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        ScopedPostRepository::$unlisted = [...$listed, ...$unlisted];

        $this->indexAction('activate-posts', 'all')
            ->assertOk()
            ->assertExactJson(['activated' => []]);

        $this->assertInactive([...$listed, ...$unlisted]);
    }

    #[Test]
    public function an_index_action_runs_in_chunks_on_the_listed_rows_only(): void
    {
        Action::$chunkCount = 1;

        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        $this->indexAction('activate-posts', 'all')->assertOk();

        $this->assertActive($listed);
        $this->assertInactive($unlisted);
    }

    /**
     * Offset chunks skip rows here: once the first chunk is activated it leaves the scope, so the second
     * chunk's offset jumps over a row that still matches.
     */
    #[Test]
    #[TestWith(['activate-posts', 'all'], 'all, each chunk committed on its own')]
    #[TestWith(['activate-posts', 'ids'], 'ids, each chunk committed on its own')]
    #[TestWith(['activate-posts-with-can-run', 'all'], 'all, every chunk in one transaction')]
    #[TestWith(['activate-posts-with-can-run', 'ids'], 'ids, every chunk in one transaction')]
    public function an_index_action_in_chunks_reaches_every_row_when_it_changes_the_column_the_index_query_filters_on(string $action, string $repositoriesMode): void
    {
        Action::$chunkCount = 1;

        ScopedPostRepository::$listsInactiveOnly = true;

        $keys = Post::factory()->count(4)->create(['is_active' => false])->modelKeys();

        $this->indexAction($action, $repositoriesMode === 'all' ? 'all' : $keys)->assertOk();

        $this->assertActive($keys);
    }

    #[Test]
    public function an_index_action_applies_its_own_index_query_on_top_of_the_repository_one(): void
    {
        $actionable = Post::factory()->create(['is_active' => false, 'category' => 'actionable']);
        $unlistedActionable = Post::factory()->create(['is_active' => false, 'category' => 'actionable']);
        $other = Post::factory()->create(['is_active' => false, 'category' => 'other']);

        ScopedPostRepository::$unlisted = [$unlistedActionable->getKey()];

        $this->indexAction('activate-active-posts', 'all')
            ->assertOk()
            ->assertExactJson(['activated' => [$actionable->getKey()]]);

        $this->assertActive([$actionable->getKey()]);
        $this->assertInactive([$unlistedActionable->getKey(), $other->getKey()]);
    }

    #[Test]
    public function an_index_action_still_narrows_its_rows_with_the_request_filters(): void
    {
        $matching = Post::factory()->create(['is_active' => false, 'title' => 'Matching']);
        $unlistedMatching = Post::factory()->create(['is_active' => false, 'title' => 'Matching']);
        $other = Post::factory()->create(['is_active' => false, 'title' => 'Other']);

        ScopedPostRepository::$unlisted = [$unlistedMatching->getKey()];

        $this->postJson(ScopedPostRepository::route('actions', query: ['action' => 'activate-posts', 'title' => 'Matching']), [
            'repositories' => 'all',
        ])
            ->assertOk()
            ->assertExactJson(['activated' => [$matching->getKey()]]);

        $this->assertInactive([$unlistedMatching->getKey(), $other->getKey()]);
    }

    #[Test]
    #[TestWith(['all'], 'all keyword')]
    #[TestWith(['explicit ids'], 'explicit id list')]
    public function an_index_action_runs_when_the_index_query_joins_another_table(string $repositoriesMode): void
    {
        $owned = Post::factory()->create(['is_active' => false]);
        $orphan = Post::factory()->create(['is_active' => false, 'user_id' => null]);

        ScopedPostRepository::$joinsUsers = true;

        $this->getJson(ScopedPostRepository::route())->assertOk()->assertJsonCount(1, 'data');

        $this->indexAction('activate-posts', $repositoriesMode === 'all' ? 'all' : [$owned->getKey(), $orphan->getKey()])
            ->assertOk()
            ->assertExactJson(['activated' => [$owned->getKey()]]);

        $this->assertInactive([$orphan->getKey()]);
    }

    #[Test]
    public function an_index_action_runs_on_every_row_when_the_index_query_excludes_none(): void
    {
        $posts = Post::factory()->count(3)->create(['is_active' => false]);

        $this->indexAction('activate-posts', 'all')->assertOk();

        $this->assertActive($posts->modelKeys());
    }

    #[Test]
    public function the_index_query_scope_of_index_actions_can_be_turned_off(): void
    {
        config()->set('restify.repositories.apply_index_query_to_actions_and_getters', false);

        [$listed, $unlisted] = $this->listedAndUnlistedPosts();

        $this->indexAction('activate-posts', 'all')->assertOk();

        $this->assertActive([...$listed, ...$unlisted]);
    }

    #[Test]
    public function a_show_action_on_a_model_the_show_query_excludes_is_not_found(): void
    {
        $shown = Post::factory()->create(['is_active' => false]);
        $notShown = Post::factory()->create(['is_active' => false]);

        ScopedPostRepository::$unshowable = [$notShown->getKey()];

        $this->getJson(ScopedPostRepository::route($notShown))->assertNotFound();
        $this->showAction($notShown)->assertNotFound();
        $this->assertInactive([$notShown->getKey()]);

        $this->showAction($shown)
            ->assertOk()
            ->assertExactJson(['activated' => [$shown->getKey()]]);
        $this->assertActive([$shown->getKey()]);
    }

    #[Test]
    public function a_show_action_ignores_a_builder_the_show_query_returns_as_the_show_route_does(): void
    {
        $other = Post::factory()->create(['is_active' => false]);
        $requested = Post::factory()->create(['is_active' => false]);

        ScopedPostRepository::$showQueryReturnsNewBuilder = true;

        $this->getJson(ScopedPostRepository::route($requested))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $requested->getKey());

        $this->showAction($requested)
            ->assertOk()
            ->assertExactJson(['activated' => [$requested->getKey()]]);

        $this->assertActive([$requested->getKey()]);
        $this->assertInactive([$other->getKey()]);
    }

    #[Test]
    public function a_show_action_is_not_limited_by_the_index_query(): void
    {
        [, $unlisted] = $this->listedAndUnlistedPosts();

        $this->getJson(ScopedPostRepository::route((string) $unlisted[0]))->assertOk();
        $this->showAction($unlisted[0])->assertOk();

        $this->assertActive([$unlisted[0]]);
    }

    #[Test]
    public function the_show_query_scope_of_show_actions_can_be_turned_off(): void
    {
        config()->set('restify.repositories.apply_show_query_to_actions_and_getters', false);

        $notShown = Post::factory()->create(['is_active' => false]);

        ScopedPostRepository::$unshowable = [$notShown->getKey()];

        $this->showAction($notShown)->assertOk();

        $this->assertActive([$notShown->getKey()]);
    }

    /**
     * @param  'all'|list<int|string>  $repositories
     */
    private function indexAction(string $action, string|array $repositories): TestResponse
    {
        return $this->postJson(ScopedPostRepository::route('actions', query: ['action' => $action]), [
            'repositories' => $repositories,
        ]);
    }

    private function showAction(Post|int $post): TestResponse
    {
        $key = $post instanceof Post ? $post->getKey() : $post;

        return $this->postJson(ScopedPostRepository::route("{$key}/actions", query: ['action' => 'activate-post']));
    }

    /**
     * Two posts the index lists and one it does not, all inactive. Keys are returned newest first, the
     * order an index action receives them in.
     *
     * @return array{list<int>, list<int>}
     */
    private function listedAndUnlistedPosts(): array
    {
        $first = Post::factory()->create(['is_active' => false]);
        $unlisted = Post::factory()->create(['is_active' => false]);
        $last = Post::factory()->create(['is_active' => false]);

        ScopedPostRepository::$unlisted = [$unlisted->getKey()];

        return [[$last->getKey(), $first->getKey()], [$unlisted->getKey()]];
    }

    /**
     * @param  list<int>  $keys
     */
    private function assertActive(array $keys): void
    {
        foreach ($keys as $key) {
            $this->assertDatabaseHas(Post::class, ['id' => $key, 'is_active' => true]);
        }
    }

    /**
     * @param  list<int>  $keys
     */
    private function assertInactive(array $keys): void
    {
        foreach ($keys as $key) {
            $this->assertDatabaseHas(Post::class, ['id' => $key, 'is_active' => false]);
        }
    }
}
