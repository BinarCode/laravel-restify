<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Repositories\Repository;

class DeclaredPrefixPostRepository extends Repository
{
    public static string $model = Post::class;

    public static string $uriKey = 'declared-prefix-posts';

    public static $prefix = 'api/v2';
}
