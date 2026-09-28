<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix;

class SecondInheritedPrefixRepository extends DeclaredPrefixBaseRepository
{
    public static string $uriKey = 'second-inherited-prefix-posts';
}
