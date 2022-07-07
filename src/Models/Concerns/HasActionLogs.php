<?php

namespace Binaryk\LaravelRestify\Models\Concerns;

use Binaryk\LaravelRestify\Models\ActionLogObserver;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasActionLogs
{
    public static function bootHasActionLogs()
    {
        if (!config('restify.logs.enable')) {
            return;
        }

        if (Restify::isRestify(request())) {
            Post::observe(ActionLogObserver::class);
        } else {
            if (config('restify.logs.all')) {
                Post::observe(ActionLogObserver::class);
            }
        }
    }

    public function actionLogs()
    {
        return $this->morphMany(Restify::actionLog(), 'actionable');
    }
}
