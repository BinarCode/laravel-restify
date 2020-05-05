<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class AppleRepository extends Repository
{
    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title'),
        ];
    }
}
