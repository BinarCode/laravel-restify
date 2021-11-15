<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Actions\Action;

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
}
