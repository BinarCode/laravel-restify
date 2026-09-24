<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Label;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class LabelRepository extends Repository
{
    public static string $model = Label::class;

    public static string $uriKey = 'labels';

    public static bool $globallySearchable = false;

    public function fields(RestifyRequest $request): array
    {
        return [];
    }
}
