<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\SelectFilter;
use Illuminate\Http\Request;

class SelectCategoryFilter extends SelectFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        // $value could be 'movie' or 'article'
        $query->where('category', $value);
    }

    public function options(Request $request)
    {
        return [
            'Movie category' => 'movie',

            'Article Category' => 'article',
        ];
    }
}
