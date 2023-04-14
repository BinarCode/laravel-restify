<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ActionRequest extends RestifyRequest
{
    protected function availableActions()
    {
        return $this->repository()->availableActions($this);
    }

    public function action()
    {
        return once(function () {
            return $this->availableActions()->first(function ($action) {
                return $this->query('action') === Action::guessUriKey($action);
            }) ?: abort(
                $this->actionExists() ? 403 : 404,
                'Action does not exists or you don\'t have enough permissions to perform it.'
            );
        });
    }

    protected function actionExists(): bool
    {
        return $this->availableActions()
            ->contains(function (mixed $action) {
                return Action::guessUriKey($action) === $this->query('action');
            });
    }

    public function builder(Action $action, int $size): Builder
    {
        return tap(RepositorySearchService::make()->search($this, $this->repository()),
            function ($query) use ($action) {
                $action::indexQuery($this, $query);
            })
            ->when($this->input('repositories') !== 'all', function ($query) {
                $query->whereKey($this->input('repositories', []));
            })
            ->latest($this->model()->getKeyName());
    }

    public function collectRepositories(Action $action, $count, Closure $callback): array
    {
        $output = [];

        if (($query = $this->builder($action, $count))->count() === 0) {
            $output[] = $callback(Collection::make([]));
        }

        $query->chunk($count, function ($chunk) use ($callback, &$output) {
            $output[] = $callback(Collection::make($chunk));
        });

        return $output;
    }

    public function isForRepositoryRequest(): bool
    {
        return $this instanceof RepositoryActionRequest;
    }
}
