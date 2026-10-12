<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\CacheSettings;

class RedeclaringChildCacheSettingsRepository extends InheritingCacheSettingsRepository
{
    public static int $cacheTtl = 600;
}
