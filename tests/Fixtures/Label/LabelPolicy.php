<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

use Binaryk\LaravelRestify\Tests\Fixtures\User\User;

class LabelPolicy
{
    public function allowRestify(?User $user = null): bool
    {
        return true;
    }
}
