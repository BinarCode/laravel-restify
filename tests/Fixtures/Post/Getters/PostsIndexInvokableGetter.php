<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters;

class PostsIndexInvokableGetter
{
    public function __invoke()
    {
        return ok('invokable works');
    }
}
