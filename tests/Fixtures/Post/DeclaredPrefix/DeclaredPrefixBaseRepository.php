<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;

abstract class DeclaredPrefixBaseRepository extends Repository
{
    public static string $model = Post::class;

    public static $prefix = 'api/v2';
}
