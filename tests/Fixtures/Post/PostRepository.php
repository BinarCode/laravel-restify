<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
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

    public function fieldsForStore(RestifyRequest $request)
    {
        return [
            Field::new('user_id'),

            Field::new('title')->storingRules('required')->messages([
                'required' => 'This field is required',
            ]),
        ];
    }

    public function filters(RestifyRequest $request)
    {
        return [
            ActiveBooleanFilter::new()->canSee(fn () => true),
            SelectCategoryFilter::new(),
            CreatedAfterDateFilter::new(),
        ];
    }

    public function resolveIndexMainMeta(RestifyRequest $request, Collection $items)
    {
        return [
            'postKey' => 'Custom Meta Value',
            'first_title' => optional($items->first())->title,
        ];
    }
}
