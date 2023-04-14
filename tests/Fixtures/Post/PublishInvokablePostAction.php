<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Illuminate\Http\Request;

class PublishInvokablePostAction
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'data' => 'Post published!',
        ]);
    }
}
