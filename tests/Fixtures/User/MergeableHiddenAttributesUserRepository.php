<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Repositories\Mergeable;

class MergeableHiddenAttributesUserRepository extends HiddenAttributesUserRepository implements Mergeable
{
    public static string $uriKey = 'mergeable-hidden-attributes-users';
}
