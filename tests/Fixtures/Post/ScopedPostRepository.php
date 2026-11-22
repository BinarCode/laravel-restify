<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * A posts repository that does not list or show every row: indexQuery leaves out $unlisted and showQuery
 * leaves out $unshowable. Like many application repositories, neither returns the builder it constrained.
 *
 * Its actions activate the posts they receive, and its getters report the posts they can reach.
 */
class ScopedPostRepository extends Repository
{
    public static string $model = Post::class;

    public static string $uriKey = 'scoped-posts';

    public static array $match = [
        'title' => 'string',
    ];

    /**
     * @var list<int>
     */
    public static array $unlisted = [];

    /**
     * @var list<int>
     */
    public static array $unshowable = [];

    /**
     * List only the posts of an existing user, through a join.
     */
    public static bool $joinsUsers = false;

    /**
     * List only inactive posts: the column the activate actions change.
     */
    public static bool $listsInactiveOnly = false;

    /**
     * What the canRun callback of `activate-posts-with-can-run` answers.
     */
    public static bool $canRun = true;

    /**
     * Make showQuery return a new, unscoped builder, which the show route ignores.
     */
    public static bool $showQueryReturnsNewBuilder = false;

    /**
     * @param  Builder<Post>|Relation<Post, Post, mixed>  $query
     */
    public static function indexQuery(RestifyRequest $request, Builder|Relation $query): void
    {
        $query->whereKeyNot(static::$unlisted);

        if (static::$listsInactiveOnly) {
            $query->where('posts.is_active', false);
        }

        if (static::$joinsUsers) {
            $query->join('users', 'users.id', '=', 'posts.user_id')->select('posts.*');
        }
    }

    /**
     * @param  Builder<Post>|Relation<Post, Post, mixed>  $query
     * @return Builder<Post>|null
     */
    public static function showQuery(RestifyRequest $request, Builder|Relation $query): ?Builder
    {
        $query->whereKeyNot(static::$unshowable);

        return static::$showQueryReturnsNewBuilder ? Post::query() : null;
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('is_active'),
        ];
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            (new class extends Action
            {
                public static $uriKey = 'activate-posts';

                /**
                 * @param  Collection<int, Post>  $posts
                 */
                public function handle(ActionRequest $request, Collection $posts): JsonResponse
                {
                    $posts->each(fn (Post $post) => $post->update(['is_active' => true]));

                    return response()->json(['activated' => $posts->map->getKey()->all()]);
                }
            })->onlyOnIndex(),

            (new class extends Action
            {
                public static $uriKey = 'activate-posts-with-can-run';

                /**
                 * @param  Collection<int, Post>  $posts
                 */
                public function handle(ActionRequest $request, Collection $posts): JsonResponse
                {
                    $posts->each(fn (Post $post) => $post->update(['is_active' => true]));

                    return response()->json(['activated' => $posts->map->getKey()->all()]);
                }
            })->onlyOnIndex()->canRun(fn (): bool => static::$canRun),

            (new class extends Action
            {
                public static $uriKey = 'activate-active-posts';

                /**
                 * @param  Builder<Post>|Relation<Post, Post, mixed>  $query
                 */
                public static function indexQuery(RestifyRequest $request, $query): void
                {
                    $query->where('category', 'actionable');
                }

                /**
                 * @param  Collection<int, Post>  $posts
                 */
                public function handle(ActionRequest $request, Collection $posts): JsonResponse
                {
                    $posts->each(fn (Post $post) => $post->update(['is_active' => true]));

                    return response()->json(['activated' => $posts->map->getKey()->all()]);
                }
            })->onlyOnIndex(),

            (new class extends Action
            {
                public static $uriKey = 'activate-post';

                public function handle(ActionRequest $request, Post $post): JsonResponse
                {
                    $post->update(['is_active' => true]);

                    return response()->json(['activated' => [$post->getKey()]]);
                }
            })->onlyOnShow(),
        ];
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            (new class extends Getter
            {
                public static $uriKey = 'reachable-posts';

                public function handle(GetterRequest $request): JsonResponse
                {
                    $chunks = $request->collectRepositories($this, 100, fn (Collection $posts): array => $posts->map->getKey()->all());

                    return response()->json([
                        'filtered' => $request->filteredQuery()->pluck('id')->sort()->values()->all(),
                        'collected' => Collection::make($chunks)->flatten()->sort()->values()->all(),
                    ]);
                }
            })->onlyOnIndex(),

            (new class extends Getter
            {
                public static $uriKey = 'post-title';

                public function handle(GetterRequest $request, ?Model $post = null): JsonResponse
                {
                    return response()->json(['title' => $post?->getAttribute('title')]);
                }
            })->onlyOnShow(),
        ];
    }
}
