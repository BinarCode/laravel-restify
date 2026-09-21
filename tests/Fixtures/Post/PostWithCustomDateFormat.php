<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

class PostWithCustomDateFormat extends Post
{
    protected $table = 'posts';

    protected $dateFormat = 'd/m/Y H:i:s';
}
