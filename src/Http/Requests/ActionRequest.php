<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
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
            }) ?: ($this->actionExists()
                ? abort(JsonResponse::HTTP_FORBIDDEN, 'You don\'t have permission to run this action.')
                : abort(JsonResponse::HTTP_NOT_FOUND, 'Action not found.'));
        });
    }

    protected function actionExists(): bool
    {
        $repository = $this->repository();

        $actions = $this->isForRepositoryRequest()
            ? $repository->resolveShowActions($this)
            : $repository->resolveIndexActions($this);

        return $actions
            ->merge($repository->resolveInvokableActions($this))
            ->contains(function (mixed $action) {
                return Action::guessUriKey($action) === $this->query('action');
            });
    }

    /**
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function builder(Action $action, int $size): Builder|Relation
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

    /**
     * @param  int  $count
     */
    public function collectRepositories(Action $action, $count, Closure $callback): array
    {
        $output = [];

        $query = $this->builder($action, $count);
        $model = $query->getModel();

        // Walk the rows by key, not by offset: an action that changes a column the query filters on moves
        // the rows it handled out of the query, and the next offset would skip as many rows that still match.
        $query->reorder()->chunkByIdDesc($count, function ($chunk) use ($callback, &$output) {
            $output[] = $callback(Collection::make($chunk));
        }, $model->getQualifiedKeyName(), $model->getKeyName());

        if ($output === []) {
            $this->ensureSelectedKeysWereFound($query);

            $output[] = $callback(Collection::make([]));
        }

        return $output;
    }

    /**
     * Keys were sent and the query matches none of them: they do not exist, or the repository mainQuery or
     * indexQuery, or the action indexQuery, leaves them out. Answer 404, as a show action does for such a
     * model, instead of running the action on nothing and reporting success.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     *
     * @throws ModelNotFoundException<Model>
     */
    private function ensureSelectedKeysWereFound(Builder|Relation $query): void
    {
        $keys = $this->input('repositories');

        if ($keys === 'all' || blank($keys)) {
            return;
        }

        throw (new ModelNotFoundException)->setModel($query->getModel()::class);
    }

    public function isForRepositoryRequest(): bool
    {
        if ($this instanceof McpActionRequest) {
            return $this->input('id') != null;
        }

        return $this instanceof RepositoryActionRequest;
    }
}
