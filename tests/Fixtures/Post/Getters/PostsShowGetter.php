<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PostsShowGetter extends Getter
{
    public static $uriKey = 'posts-show-getter';

    public function handle(Request $request, ?Model $model = null)
    {
        return ok('show works');
    }
}
