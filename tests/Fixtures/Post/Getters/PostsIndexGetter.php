<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostsIndexGetter extends Getter
{
    public function handle(Request $request): JsonResponse
    {
        return ok('it works', 200);
    }
}
