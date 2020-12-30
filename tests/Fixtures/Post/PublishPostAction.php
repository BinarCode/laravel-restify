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

    public static function indexQuery(RestifyRequest $request, $query)
    {
        $query->whereNotNull('published_at');
    }

    public function handle(ActionRequest $request, Collection $models): JsonResponse
    {
        static::$applied[] = $models;

        return $this->response()->data(['succes' => 'true'])->respond();
    }
}
