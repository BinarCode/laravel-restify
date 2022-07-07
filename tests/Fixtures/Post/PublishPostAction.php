<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class PublishPostAction extends Action
{
    public static $applied = [];

    public static $uriKey = 'publish-post-action';

    public static function indexQuery(RestifyRequest $request, $query)
    {
        $query->whereNotNull('published_at');
    }

    public function handle(ActionRequest $request, Collection $models): JsonResponse
    {
        static::$applied[] = $models;

        $models->each(fn(Post $post) => $post->update([
            'is_active' => true,
        ]));

        return data(['succes' => 'true']);
    }
}
