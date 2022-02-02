<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnauthenticatedActionGetter extends Getter
{
    public static $uriKey = 'posts-unauthenticated-getter';

    public function handle(Request $request): JsonResponse
    {
        return ok('not authenticated works');
    }
}
