<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Actions\Action;

class ActionRequest extends RestifyRequest
{
    protected function availableActions()
    {
        return $this->newRepository()->availableActions($this);
    }

    public function action(): Action
    {
        return once(function () {
            return $this->availableActions()->first(function ($action) {
                return $action->uriKey() == $this->query('action');
            }) ?: abort($this->actionExists() ? 403 : 404);
        });
    }

    protected function actionExists()
    {
        return $this->availableActions()->contains(function (Action $action) {
            return $action->uriKey() == $this->query('action');
        });
    }
}
