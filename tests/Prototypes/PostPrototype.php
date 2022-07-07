<?php

namespace Binaryk\LaravelRestify\Tests\Prototypes;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;

class PostPrototype extends Prototypeable
{
    public function model(): ?Post
    {
        return $this->model;
    }
}
