<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Comment;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class CommentRepository extends Repository
{
    public static string $model = Comment::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('comment'),
        ];
    }
}
