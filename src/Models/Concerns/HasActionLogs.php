<?php

namespace Binaryk\LaravelRestify\Models\Concerns;

use Binaryk\LaravelRestify\Models\ActionLogObserver;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasActionLogs
{
    public static function bootHasActionLogs()
    {
        if (! config('restify.logs.enable')) {
            return;
        }

        if (Restify::isRestify(request())) {
            static::observe(ActionLogObserver::class);
        } else {
            if (config('restify.logs.all')) {
                static::observe(ActionLogObserver::class);
            }
        }
    }

    public function actionLogs()
    {
        return $this->morphMany(Restify::actionLog(), 'actionable');
    }
}
