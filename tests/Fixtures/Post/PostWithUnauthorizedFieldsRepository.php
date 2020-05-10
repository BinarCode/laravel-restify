<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class PostWithUnauthorizedFieldsRepository extends Repository
{
    public static $model = Post::class;

    public static $uriKey = 'post-with-unathorized-fields';

    public static $globallySearchable = false;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('user_id'),

            Field::new('title')->canUpdate(fn () => $_SERVER['posts.authorizable.title']),
        ];
    }
}
