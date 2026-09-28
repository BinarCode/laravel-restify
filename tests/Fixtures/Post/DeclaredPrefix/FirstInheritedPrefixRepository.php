<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post\DeclaredPrefix;

class FirstInheritedPrefixRepository extends DeclaredPrefixBaseRepository
{
    public static string $uriKey = 'first-inherited-prefix-posts';
}
