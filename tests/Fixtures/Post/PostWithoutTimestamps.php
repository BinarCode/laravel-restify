<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

class PostWithoutTimestamps extends Post
{
    public $timestamps = false;

    protected $table = 'posts';
}
