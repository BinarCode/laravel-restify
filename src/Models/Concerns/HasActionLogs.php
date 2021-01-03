<?php

namespace Binaryk\LaravelRestify\Models\Concerns;

use Binaryk\LaravelRestify\Restify;

trait HasActionLogs
{
    public function actionLogs()
    {
        return $this->morphMany(Restify::actionLog(), 'actionable');
    }
}
