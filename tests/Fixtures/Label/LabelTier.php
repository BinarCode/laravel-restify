<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

enum LabelTier: string
{
    case Gold = 'gold';
    case Silver = 'silver';
}
