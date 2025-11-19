<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

class PostsShowInvokableGetter
{
    public function __invoke()
    {
        return ok('show works');
    }
}
