<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;

class PostUnauthorizedFieldRepository extends Repository implements Mergeable
{
    public static $model = Post::class;

    public static $globallySearchable = false;

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return 'posts-unauthorized-fields';
    }

    /**
     * @param RestifyRequest $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('image')->readonly(),

            Field::new('user_id'),

            Field::new('title'),

            Field::new('description')->canStore(fn () => $_SERVER['posts.description.authorized'] ?? false),
        ];
    }
}
