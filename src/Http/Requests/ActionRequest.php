<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Closure;
use Illuminate\Support\Collection;

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

    public function collectRepositories($count, Closure $callback)
    {
        $output = [];

        RepositorySearchService::instance()->search($this, $this->repository())
        ->when($this->input('repositories') !== 'all', function ($query) {
            $query->whereKey($this->input('repositories', []));
        })->latest($this->model()->getKeyName())
        ->chunk($count, function ($chunk) use ($callback, &$output) {
            $output[] = $callback(Collection::make($chunk));
        });

        return $output;
    }
}
