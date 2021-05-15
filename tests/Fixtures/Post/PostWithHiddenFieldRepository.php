<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class PostWithHiddenFieldRepository extends Repository
{
    public static $model = Post::class;

    public static $uriKey = 'post-with-hidden-fields';

    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::new('user_id')->hidden(),

            Field::new('category')->hidden()->value('Append category for a hidden field.'),

            Field::new('title'),
        ];
    }
}
