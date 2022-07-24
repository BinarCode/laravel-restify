<?php

namespace Binaryk\LaravelRestify\Models;

use Binaryk\LaravelRestify\Http\Requests\IndexRepositoryActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActionLogObserver
{
    public function created(Model $model): void
    {
        if (! $this->tryLoggingActionRequest($model)) {
            Restify::actionLog()
                ->forRepositoryStored($model, request()?->user())
                ->save();
        }
    }

    public function updating(Model $model): void
    {
        if (! $this->tryLoggingActionRequest($model)) {
            Restify::actionLog()
                ->forRepositoryUpdated($model, request()?->user())
                ->save();
        }
    }

    public function deleted(Model $model): void
    {
        if (! $this->tryLoggingActionRequest($model)) {
            Restify::actionLog()
                ->forRepositoryDestroy($model, request()?->user())
                ->save();
        }
    }

    private function tryLoggingActionRequest(Model $model): bool
    {
        $isPerformingAction = in_array($_SERVER['restify.requestClass'] ?? null, [
            IndexRepositoryActionRequest::class,
            RepositoryActionRequest::class,
        ], true);

        if ($isPerformingAction) {
            try {
                return Restify::actionLog()
                    ->forRepositoryAction(app($_SERVER['restify.requestClass'])->action(), $model, Auth::user())
                    ->save();
            } catch (Throwable) {
            }
        }

        return false;
    }
}
