<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class PostRepository extends Repository
{
    public static $model = Post::class;

    public static $title = 'title';

    public static $search = [
        'id',
        'title',
    ];

    public static $match = [
        'title' => RestifySearchable::MATCH_TEXT,
    ];

    public static function indexQuery(RestifyRequest $request, $query)
    {
        if (isset($_SERVER['restify.post.indexQueryCallback']) && is_callable($_SERVER['restify.post.indexQueryCallback'])) {
            call_user_func($_SERVER['restify.post.indexQueryCallback'], $query);
        }
    }

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('user_id'),

            Field::new('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),

            Field::new('description')->storingRules('required')->messages([
                'required' => 'Description field is required',
            ]),
        ];
    }

    public function fieldsForStore(RestifyRequest $request): array
    {
        return [
            Field::new('user_id'),

            Field::new('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),
        ];
    }

    public function fieldsForStoreBulk(RestifyRequest $request)
    {
        return [
            Field::new('title')->storeBulkRules('required')->messages([
                'required' => 'This field is required',
            ]),

            Field::new('user_id'),
        ];
    }

    public function fieldsForUpdateBulk(RestifyRequest $request)
    {
        return [
            Field::new('title')->updateBulkRules('required')->messages([
                'required' => 'This field is required',
            ]),

            Field::new('user_id'),
        ];
    }

    public function filters(RestifyRequest $request)
    {
        return [
            ActiveBooleanFilter::new()->canSee(fn () => true),
            SelectCategoryFilter::new(),
            CreatedAfterDateFilter::new(),
            InactiveFilter::new(),
        ];
    }

    public function resolveIndexMainMeta(RestifyRequest $request, Collection $items, array $paginator): array
    {
        return [
            'postKey' => 'Custom Meta Value',
            'first_title' => optional($items->first())->title,
        ];
    }

    public function actions(RestifyRequest $request)
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
        ];
    }
}
