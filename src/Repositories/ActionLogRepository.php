<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\ActionLog;

class ActionLogRepository extends Repository
{
    public static $model = ActionLog::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            field('batch_id')->readonly(),
            field('user_id')->readonly(),
            field('name')->readonly(),
            field('actionable_type')->readonly(),
            field('actionable_id')->readonly(),
            field('target_type')->readonly(),
            field('target_id')->readonly(),
            field('model_type')->readonly(),
            field('model_id')->readonly(),
            field('fields')->readonly(),
            field('status')->readonly(),
            field('original')->readonly(),
            field('changes')->readonly(),
            field('exception')->readonly(),
        ];
    }
}
