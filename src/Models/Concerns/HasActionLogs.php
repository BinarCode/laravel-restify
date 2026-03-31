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
    public static function bootHasActionLogs(): void
    {
        if (! config('restify.logs.enable')) {
            return;
        }

        if (Restify::isRestify(request()) || config('restify.logs.all')) {
            static::registerActionLogListeners();
        }
    }

    protected static function registerActionLogListeners(): void
    {
        $observer = app(ActionLogObserver::class);

        static::created(fn (Model $model) => $observer->created($model));
        static::updating(fn (Model $model) => $observer->updating($model));
        static::deleted(fn (Model $model) => $observer->deleted($model));
    }

    public function actionLogs()
    {
        return $this->morphMany(Restify::actionLog(), 'actionable');
    }
}
