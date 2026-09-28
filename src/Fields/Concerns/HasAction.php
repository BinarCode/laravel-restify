<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HasAction
{
    public ?Action $actionHandler = null;

    public function action(Action $action): self
    {
        if (! $action->onlyOnShow()) {
            $key = $action::$uriKey;

            abort(400, "The action $key should be only for show.");
        }

        $this->actionHandler = $action;

        return $this;
    }

    public function isActionable(): bool
    {
        return $this->actionHandler instanceof Action;
    }

    /**
     * @throws UnauthorizedException
     */
    public function authorizeActionToSee(Request $request): void
    {
        $action = $this->actionHandler;

        if (! $action instanceof Action) {
            return;
        }

        if (! $action->authorizedToSee($request)) {
            throw UnauthorizedException::make('Not authorized to run this action.');
        }
    }

    /**
     * @throws UnauthorizedException
     */
    public function authorizeActionToRun(Request $request, ?Model $model): void
    {
        $action = $this->actionHandler;

        if (! $action instanceof Action) {
            return;
        }

        if (! $action->authorizedToRun($request, $model)) {
            throw UnauthorizedException::make('Not authorized to run this action.');
        }
    }
}
