<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

class EnumKeyedLabelRepository extends LabelRepository
{
    public static string $model = EnumKeyedLabel::class;

    public static string $uriKey = 'tiers';
}
