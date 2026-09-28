<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;

class DeclaredPrefixPostRepository extends Repository
{
    public static string $model = Post::class;

    public static string $uriKey = 'declared-prefix-posts';

    public static $prefix = 'api/v2';
}
