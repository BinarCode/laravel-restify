<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class PostWithCustomMiddlewareRepository extends Repository
{
    public static $model = Post::class;

    public static $middleware = [
        PostAbortMiddleware::class,
    ];

    public static $uriKey = 'post-with-middleware';

    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('title'),
        ];
    }
}
