<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

class StringableKeyedLabelRepository extends LabelRepository
{
    public static string $model = StringableKeyedLabel::class;

    public static string $uriKey = 'badges';
}
