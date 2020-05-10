<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class PostAuthorizeRepository extends Repository
{
    public static $model = Post::class;

    public static $globallySearchable = false;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('user_id'),

            Field::new('title')
                ->canSee(fn () => $_SERVER['postAuthorize.can.see.title'])
                ->showCallback(fn ($value) => strtoupper($value)),

            Field::new('description')->storingRules('required')->messages([
                'required' => 'Description field is required',
            ]),
        ];
    }
}
