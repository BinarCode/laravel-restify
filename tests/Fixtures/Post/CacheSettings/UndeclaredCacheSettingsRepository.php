<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;

class UndeclaredCacheSettingsRepository extends Repository
{
    public static string $model = Post::class;
}
