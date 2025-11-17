<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostsFilteredQueryGetter extends Getter
{
    public static $uriKey = 'posts-filtered-query-getter';

    public function handle(Request $request): JsonResponse
    {
        $query = $request->filteredQuery();

        $count = $query->count();

        return response()->json([
            'message' => 'filtered query works',
            'count' => $count,
        ]);
    }
}
