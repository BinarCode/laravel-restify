<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\ActionLog;

class ActionLogRepository extends Repository
{
    public static $model = ActionLog::class;

    public function fields(RestifyRequest $request)
    {
        return [
            field('actionable_type'),

            field('actionable_id')
        ];
    }
}
