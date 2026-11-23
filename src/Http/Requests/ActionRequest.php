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
    /**
     * The alias the key is read under while an index action collects the keys of its selection.
     */
    private const KEY_ALIAS = 'restify_action_key';

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

        // Read the keys of the selection first, in the requested order, then load the models chunk by chunk.
        // Paging the query itself while the action runs would skip rows once it changes a column the query
        // filters on.
        $keys = $this->selectedKeys($query, $count);

        foreach ($keys->chunk($count) as $chunk) {
            $models = $this->modelsInKeyOrder($query, $chunk->values());

            if ($models->isNotEmpty()) {
                $output[] = $callback($models);
            }
        }

        if ($output === []) {
            $this->ensureSelectedKeysAreInScope();

            $output[] = $callback(Collection::make([]));
        }

        return $output;
    }

    /**
     * The keys of the rows the action query selects, in its order. The query is paged as it is, keeping its
     * orders and limit, before the action changes anything, and the key is read under an alias. A select list
     * the query sets is kept, since its orders may refer to its aliases; without one only the key is read.
     * Rows are not kept, only their keys, so at most one page of rows is in memory at a time.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Collection<int, mixed>
     */
    private function selectedKeys(Builder|Relation $query, int $count): Collection
    {
        $keysQuery = (clone $query)->toBase();
        $key = $query->getModel()->getQualifiedKeyName().' as '.self::KEY_ALIAS;

        if ($keysQuery->columns === null && ! $keysQuery->distinct) {
            $keysQuery->select($key);
        } else {
            // A distinct query keeps every column, so it still selects the columns it orders by.
            $keysQuery->columns ??= ['*'];
            $keysQuery->addSelect($key);
        }

        $keys = [];

        $keysQuery->chunk($count, function (Collection $rows) use (&$keys): void {
            foreach ($rows as $row) {
                $keys[] = data_get($row, self::KEY_ALIAS);
            }
        });

        return Collection::make($keys)->unique()->values();
    }

    /**
     * The models of the given keys, loaded through the action query and returned in the order of the keys.
     * A row that left the query since its key was read is skipped.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @param  Collection<int, mixed>  $keys
     * @return Collection<int, Model>
     */
    private function modelsInKeyOrder(Builder|Relation $query, Collection $keys): Collection
    {
        $chunkQuery = (clone $query)->whereKey($keys->all());

        // The keys were read with any limit and offset applied already, and the models are put in their order
        // below, so the database does not sort them again.
        $base = $chunkQuery instanceof Relation ? $chunkQuery->getBaseQuery() : $chunkQuery->getQuery();
        $base->limit = null;
        $base->offset = null;
        $chunkQuery->reorder();

        // Match on the key as the database returned it, as the keys were read, not on a cast value.
        $models = $chunkQuery->get()->keyBy(
            fn (Model $model): string => self::keyString($model->getRawOriginal($model->getKeyName()))
        );

        return $keys
            ->map(fn (mixed $key): ?Model => $models->get(self::keyString($key)))
            ->filter()
            ->values();
    }

    /**
     * A key in a form that compares equal however the database returned it: an integer key may come back as
     * a string.
     */
    private static function keyString(mixed $key): string
    {
        return is_scalar($key) ? (string) $key : '';
    }

    /**
     * Keys were sent and none of them is in the repository scope: they do not exist, or the repository mainQuery,
     * or its indexQuery when that applies to actions, leaves them out. Answer 404, as a show action does for such
     * a model. Keys that only the action indexQuery or the request filters leave out still reach the action as an
     * empty collection.
     *
     * @throws ModelNotFoundException<Model>
     */
    private function ensureSelectedKeysAreInScope(): void
    {
        $keys = $this->input('repositories');

        if ($keys === 'all' || blank($keys)) {
            return;
        }

        $repository = $this->repository();

        $query = tap($repository::query($this), fn (Builder|Relation $query) => $repository::mainQuery($this, $query));

        if (config('restify.repositories.apply_index_query_to_actions_and_getters', true)) {
            $repository::indexQuery($this, $query);
        }

        if ($query->whereKey($keys)->exists()) {
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
