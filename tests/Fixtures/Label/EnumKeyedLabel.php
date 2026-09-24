<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

class EnumKeyedLabel extends Label
{
    protected $casts = [
        'code' => LabelTier::class,
    ];
}
