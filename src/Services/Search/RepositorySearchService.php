<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Events\AdvancedFiltersApplied;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\AdvancedFiltersCollection;
use Binaryk\LaravelRestify\Filters\Concerns\AppliesRelationJoin;
use Binaryk\LaravelRestify\Filters\RelatablePerPage;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Throwable;

class RepositorySearchService
{
    use AppliesRelationJoin;

    /** @var Repository */
    protected $repository;

    public function search(RestifyRequest $request, Repository $repository): Builder|Relation
    {
        $this->repository = $repository;

        $scoutQuery = null;
        $shouldUseScout = $this->isScoutAvailable($repository);

        if ($shouldUseScout) {
            $scoutQuery = $this->initializeQueryUsingScout($request, $repository);
        }

        $query = $this->prepareMatchFields(
            $request,
            $shouldUseScout
                ? $this->prepareRelations($request, $scoutQuery ?? $repository::query($request))
                : $this->prepareSearchFields(
                    $request,
                    $this->prepareRelations($request, $scoutQuery ?? $repository::query($request)),
                ),
        );

        $query = $this->applyFilters($request, $repository, $query);

        $query = $this->applyGroupBy($request, $repository, $query);

        $ordersBuilder = $this->prepareOrders($request, $query);

        return $this->applyKeyTiebreaker(tap(
            tap($ordersBuilder, $this->applyMainQuery($request, $repository)),
            $this->applyIndexQuery($request, $repository)
        ));
    }

    /**
     * Order rows that tie on every other order by the primary key, so pagination never
     * repeats or skips a row. The key follows the last order's direction, which keeps a
     * (column, key) index usable; it is descending after a raw order. Grouped, distinct
     * and union queries are left alone, as are queries already ordered by the key and
     * pivot models, whose tables usually have no primary key column.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    protected function applyKeyTiebreaker(Builder|Relation $query): Builder|Relation
    {
        $base = $query instanceof Relation ? $query->getBaseQuery() : $query->getQuery();

        if (empty($base->orders) || ! empty($base->groups) || $base->distinct !== false || ! empty($base->unions)) {
            return $query;
        }

        $model = $query->getModel();

        if ($model instanceof Pivot) {
            return $query;
        }

        $keys = [$model->getKeyName(), $model->getQualifiedKeyName()];

        foreach ($base->orders as $order) {
            if (is_array($order) && in_array($order['column'] ?? null, $keys, true)) {
                return $query;
            }
        }

        $last = end($base->orders);

        return $query->orderBy(
            $model->getQualifiedKeyName(),
            is_array($last) && ($last['direction'] ?? null) === 'asc' ? 'asc' : 'desc',
        );
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     */
    public function prepareMatchFields(RestifyRequest $request, $query)
    {
        $this->repository::collectMatches($request, $this->repository)->apply($request, $query);

        return $query;
    }

    /**
     * Resolve orders.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function prepareOrders(RestifyRequest $request, $query)
    {
        $collection = $this->repository::collectSortables($request, $this->repository);

        if ($collection->isEmpty()) {
            return empty($query->getQuery()->orders)
                ? $query->latest($query->getModel()->getQualifiedKeyName())
                : $query;
        }

        $collection->qualifyColumns($query->getModel());
        $collection->apply($request, $query);

        return $query;
    }

    public function prepareRelations(RestifyRequest $request, Builder|Relation $query): Builder|Relation
    {
        $eager = ($this->repository)::collectRelated()
            ->forRequest($request, $this->repository)
            ->map(
                fn ($relation) => $relation instanceof EagerField
                    ? $relation->relation
                    : $relation
            )
            ->values()
            ->unique()
            ->all();

        if (empty($eager)) {
            return $query;
        }

        $declared = array_values(array_filter($eager, 'is_string'));

        $filtered = array_filter(
            $request->related()->makeTree(),
            fn (string $relationPath): bool => $this->isDeclared($relationPath, $declared)
                && $this->relationExists($query, $relationPath),
        );

        return $query->with(
            array_merge(
                ($this->repository)::collectWiths($request, $this->repository)->all(),
                $this->limitedEagerLoads($filtered),
            ),
        );
    }

    /**
     * @param  array<array-key, string>  $relationPaths
     * @return array<array-key, string|Closure>
     */
    private function limitedEagerLoads(array $relationPaths): array
    {
        if (! RelatablePerPage::isRequested()) {
            return $relationPaths;
        }

        $eagerLoads = [];

        foreach ($relationPaths as $relationPath) {
            $default = $this->defaultRelatablePerPage($relationPath);

            $eagerLoads[$relationPath] = function (Relation $relation) use ($relationPath, $default): void {
                if ($relation instanceof HasMany || $relation instanceof MorphMany || $relation instanceof BelongsToMany) {
                    $relation->limit(RelatablePerPage::forPath($relationPath, $default));
                }
            };
        }

        return $eagerLoads;
    }

    private function defaultRelatablePerPage(string $relationPath): int
    {
        /** @var class-string<Repository> $repositoryClass */
        $repositoryClass = $this->repository::class;

        foreach (explode('.', $relationPath) as $segment) {
            $repositoryClass = $this->relatedRepositoryClass($repositoryClass, $segment);

            if ($repositoryClass === null) {
                return Repository::$defaultRelatablePerPage;
            }
        }

        return $repositoryClass::$defaultRelatablePerPage;
    }

    /**
     * @param  class-string<Repository>  $repositoryClass
     * @return class-string<Repository>|null
     */
    private function relatedRepositoryClass(string $repositoryClass, string $relation): ?string
    {
        foreach ($repositoryClass::include() as $field) {
            if ($field instanceof EagerField && $field->relation === $relation) {
                /** @var class-string<Repository> $relatedRepositoryClass */
                $relatedRepositoryClass = $field->repositoryClass;

                return $relatedRepositoryClass;
            }
        }

        $relationInstance = $repositoryClass::newModel()->{$relation}();

        if (! $relationInstance instanceof Relation) {
            return null;
        }

        /** @var class-string<Repository>|null */
        return Restify::repositoryForModel($relationInstance->getRelated());
    }

    /**
     * @param  list<string>  $declared
     */
    private function isDeclared(string $relationPath, array $declared): bool
    {
        foreach ($declared as $declaredPath) {
            if ($relationPath === $declaredPath || str_starts_with($relationPath, $declaredPath.'.')) {
                return true;
            }
        }

        return false;
    }

    private function relationExists(Builder|Relation $query, string $relationPath): bool
    {
        try {
            foreach (explode('.', $relationPath) as $segment) {
                $relation = $query->getRelation($segment);

                if (! $relation instanceof Relation) {
                    return false;
                }

                $query = $relation->getRelated()->newModelQuery();
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function prepareSearchFields(RestifyRequest $request, $query)
    {
        $search = $request->input('search');

        if (empty($search) || ! is_scalar($search)) {
            return $query;
        }

        $model = $query->getModel();

        // Collect all searchables and conditionally apply JOINs for BelongsTo relationships
        $searchablesCollection = $this->repository::collectSearchables($request, $this->repository);

        if (config('restify.search.use_joins_for_belongs_to', false)) {
            $this->applyBelongsToJoins($query, $searchablesCollection, $request);
        }

        $query->where(function (Builder $query) use ($search, $model, $request, $searchablesCollection) {
            $connectionType = $model->getConnection()->getDriverName();

            $hasSearchableFields = $searchablesCollection->isNotEmpty();

            // Handle primary key search if conditions are met
            $canSearchPrimaryKey = is_numeric($search) &&
                in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                $hasSearchableFields &&
                in_array($query->getModel()->getKeyName(), $this->repository::searchables());

            if ($canSearchPrimaryKey) {
                $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
            }

            // Apply all searchables using the unified collection
            $searchablesCollection->apply($request, $query, (string) $search);
        });

        return $query;
    }

    protected function applyIndexQuery(RestifyRequest $request, Repository $repository)
    {
        if ($request->isIndexRequest() || $request->isGlobalRequest()) {
            return fn ($query) => $repository::indexQuery($request, $query);
        }

        if ($request->isShowRequest()) {
            return fn ($query) => $repository::showQuery($request, $query);
        }

        return fn ($query) => $query;
    }

    public function initializeQueryUsingScout(RestifyRequest $request, Repository $repository): Builder
    {
        try {
            /**
             * @var Collection $keys
             */
            $keys = tap(
                is_null($request->input('search')) ? $repository::newModel() : $repository::newModel()->search($request->input('search')),
                function ($scoutBuilder) use ($repository, $request) {
                    return $repository::scoutQuery($request, $scoutBuilder);
                }
            )->take($repository::$scoutSearchResults)->get()->map->getKey();

            return $repository::newModel()->newQuery()->whereIn(
                $repository::newModel()->getQualifiedKeyName(),
                $keys->all()
            );
        } catch (\Exception $e) {
            // Scout operation failed, fall back to database search
            return $repository::query($request);
        }
    }

    protected function applyMainQuery(RestifyRequest $request, Repository $repository): callable
    {
        return function (Builder|Relation $query) use ($request, $repository) {
            $eagerLoads = $query->getEagerLoads();

            $withs = $repository::collectWiths($request, $repository)
                ->reject(fn (string $relation): bool => array_key_exists($relation, $eagerLoads))
                ->all();

            return $repository::mainQuery($request, $query->with($withs));
        };
    }

    protected function applyFilters(RestifyRequest $request, Repository $repository, $query)
    {
        event(
            new AdvancedFiltersApplied(
                $repository,
                AdvancedFiltersCollection::collectQueryFilters($request, $repository)
                    ->apply($request, $query),
                $request->filters(),
            )
        );

        return $query;
    }

    protected function applyGroupBy(RestifyRequest $request, Repository $repository, $query)
    {
        if (! $request->has('group_by')) {
            return $query;
        }

        $model = $query->getModel();
        $groupByColumns = explode(',', $request->input('group_by'));

        foreach ($groupByColumns as $column) {
            if (! in_array($column, $repository::$groupBy)) {
                abort(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, sprintf(
                    'The column [%s] is not allowed for grouping. Allowed columns are: %s',
                    $column,
                    implode(', ', $repository::$groupBy)
                ));
            }
            $query->groupBy($model->qualifyColumn($column));
        }

        return $query;
    }

    /**
     * Check if Scout is available and properly configured for the given repository.
     */
    protected function isScoutAvailable(Repository $repository): bool
    {
        // First check if the model uses Scout at all
        if (! $repository::usesScout()) {
            return false;
        }

        try {
            // Check if Scout service is bound in the container
            if (! app()->bound('Laravel\Scout\EngineManager')) {
                return false;
            }

            // Try to get the Scout engine - this will fail if driver is not properly configured
            $engine = app('Laravel\Scout\EngineManager')->engine();

            // Basic connectivity test - try to create a search builder (this is lightweight)
            $repository::newModel()->search('');

            return true;
        } catch (\Exception $e) {
            // Scout is not available or misconfigured
            return false;
        }
    }

    /**
     * Preemptively apply JOINs for all BelongsTo relationships that will be searched.
     */
    private function applyBelongsToJoins(
        $query,
        SearchablesCollection $searchablesCollection,
        RestifyRequest $request
    ): void {
        $searchablesCollection
            ->onlyBelongsTo()
            ->each(function (SearchableFilter $searchable) use ($query, $request) {
                $belongsToField = $searchable->belongsToField;

                // Verify authorization
                if (! $belongsToField->authorize($request)) {
                    return;
                }

                try {
                    // Get relationship details with error handling
                    $relatedModel = $belongsToField->getRelatedModel($this->repository);
                    $relatedTable = $relatedModel->getTable();
                    $mainTable = $this->repository->model()->getTable();

                    $relation = $belongsToField->getRelation($this->repository);
                    $foreignKey = $relation->getForeignKeyName();
                    $ownerKey = $relation->getOwnerKeyName();

                    // Build fully qualified column names
                    $this->ensureLeftJoin(
                        $query,
                        $relatedTable,
                        $mainTable.'.'.$foreignKey,
                        $relatedTable.'.'.$ownerKey,
                        $mainTable,
                    );
                } catch (\Exception $e) {
                    // Skip this JOIN if the relationship doesn't exist or has issues
                    // This allows the code to gracefully handle missing relationships
                }
            });
    }

    public static function make(): static
    {
        return new static;
    }
}
