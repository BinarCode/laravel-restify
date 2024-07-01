<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\UnauthenticatedActionGetter;
use Illuminate\Support\Collection;

class PostRepository extends Repository
{
    public static $model = Post::class;

    public static string $title = 'title';

    public static array $search = [
        'id',
        'title',
    ];

    public static array $match = [
        'title' => RestifySearchable::MATCH_TEXT,
    ];

    public static array $middleware = [];

    public static function indexQuery(RestifyRequest $request, $query)
    {
        if (isset($_SERVER['restify.post.indexQueryCallback']) && is_callable($_SERVER['restify.post.indexQueryCallback'])) {
            call_user_func($_SERVER['restify.post.indexQueryCallback'], $query);
        }
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('user_id'),

            field('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),

            field('description')->storingRules('required')->messages([
                'required' => 'Description field is required',
            ]),
        ];
    }

    public function fieldsForStore(RestifyRequest $request): array
    {
        return [
            field('user_id'),

            field('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),

            field('is_active'),
        ];
    }

    public function fieldsForStoreBulk(RestifyRequest $request)
    {
        return [
            field('title')->storeBulkRules('required')->messages([
                'required' => 'This field is required',
            ]),

            field('user_id'),
        ];
    }

    public function fieldsForUpdateBulk(RestifyRequest $request)
    {
        return [
            field('title')->updateBulkRules('required')->messages([
                'required' => 'This field is required',
            ]),

            field('user_id'),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [
            ActiveBooleanFilter::new()->canSee(fn () => true),
            SelectCategoryFilter::new(),
            CreatedAfterDateFilter::new(),
            InactiveFilter::new(),
            ValueFilter::new(),
        ];
    }

    public function resolveIndexMainMeta(RestifyRequest $request, Collection $items, array $paginator): array
    {
        return [
            'postKey' => 'Custom Meta Value',
            'first_title' => optional($items->first())->title,
        ];
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            PublishPostAction::new()
                ->onlyOnShow(
                    $_SERVER['actions.posts.publish.onlyOnShow'] ?? false,
                ),
            InvalidatePostAction::new()
                ->onlyOnShow(
                    $_SERVER['actions.posts.onlyOnShow'] ?? true
                )
                ->canSee(function (ActionRequest $request) {
                    return $_SERVER['actions.posts.invalidate'] ?? true;
                }),
            new PublishInvokablePostAction,
        ];
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            PostsIndexGetter::make(),
            PostsShowGetter::make()->onlyOnShow(),
            UnauthenticatedActionGetter::make()->withoutMiddleware(AuthorizeRestify::class),
            new PostsShowInvokableGetter,
            new PostsIndexInvokableGetter,
        ];
    }
}
