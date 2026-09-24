<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

use Illuminate\Database\Eloquent\Casts\AsStringable;

class StringableKeyedLabel extends Label
{
    protected $casts = [
        'code' => AsStringable::class,
    ];
}
