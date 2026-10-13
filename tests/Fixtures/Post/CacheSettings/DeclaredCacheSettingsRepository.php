<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings;

use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;

class DeclaredCacheSettingsRepository extends Repository
{
    public static string $model = Post::class;

    public static int $cacheTtl = 600;

    public static bool $cacheEnabled = false;

    public static ?string $cacheStore = 'secondary';

    /**
     * @var array<int, string>
     */
    public static array $cacheTags = ['declared'];
}
