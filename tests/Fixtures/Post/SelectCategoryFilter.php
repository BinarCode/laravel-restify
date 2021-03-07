<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\SelectFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class SelectCategoryFilter extends SelectFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('category', $value);
    }

    public function options(Request $request): array
    {
        return [
            'movie' => 'Movie category',

            'article' => 'Article Category',
        ];
    }
}
