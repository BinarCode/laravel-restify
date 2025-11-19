<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Events\AdvancedFiltersApplied;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\AdvancedFiltersCollection;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Throwable;

class RepositorySearchService
{
    /** * @var Repository */
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

        return tap(
            tap($ordersBuilder, $this->applyMainQuery($request, $repository)),
            $this->applyIndexQuery($request, $repository)
        );
    }

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
        $collection = $this->repository::collectSorts($request, $this->repository);

        if ($collection->isEmpty()) {
            return empty($query->getQuery()->orders)
                ? $query->latest($query->getModel()->getQualifiedKeyName())
                : $query;
        }

        $collection->apply($request, $query);

        return $query;
    }

    public function prepareRelations(RestifyRequest $request, Builder|Relation $query)
    {
        $eager = ($this->repository)::collectRelated()
            ->forRequest($request, $this->repository)
            ->map(
                fn($relation) => $relation instanceof EagerField
                    ? $relation->relation
                    : $relation
            )
            ->values()
            ->unique()
            ->all();

        if (empty($eager)) {
            return $query;
        }

        $filtered = collect($request->related()->makeTree())->filter(fn(string $relationships) => in_array(
            str($relationships)->whenContains('.', fn(Stringable $string) => $string->before('.'))->toString(),
            $eager,
            true,
        ))->filter(function ($relation) use ($query) {
            try {
                return $query->getRelation($relation) instanceof Relation;
            } catch (Throwable) {
                return false;
            }
        })->all();

        return $query->with(
            array_merge($filtered, ($this->repository)::collectWiths($request, $this->repository)->all()),
        );
    }

    public function prepareSearchFields(RestifyRequest $request, $query)
    {
        $search = $request->input('search');

        if (empty($search)) {
            return $query;
        }

        $model = $query->getModel();

        // Collect all searchables and conditionally apply JOINs for BelongsTo relationships
        $searchablesCollection = $this->repository::collectSearchables($request, $this->repository);

        if (config('restify.search.use_joins_for_belongs_to', false)) {
            $this->applyBelongsToJoins($query, $searchablesCollection, $request);
        }

        $query->where(function ($query) use ($search, $model, $request, $searchablesCollection) {
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
            $searchablesCollection->apply($request, $query, $search);
        });

        return $query;
    }

    protected function applyIndexQuery(RestifyRequest $request, Repository $repository)
    {
        if ($request->isIndexRequest() || $request->isGlobalRequest()) {
            return fn($query) => $repository::indexQuery($request, $query);
        }

        if ($request->isShowRequest()) {
            return fn($query) => $repository::showQuery($request, $query);
        }

        return fn($query) => $query;
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
        return fn($query) => $repository::mainQuery($request, $query->with($repository::collectWiths(
            $request,
            $repository
        )->all()));
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
                abort(422, sprintf(
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

                    $relation = $belongsToField->getRelation($this->repository);
                    $foreignKey = $relation->getForeignKeyName();
                    $ownerKey = $relation->getOwnerKeyName();

                    // Build fully qualified column names
                    $localTableForeignKey = $this->repository->model()->getTable() . '.' . $foreignKey;
                    $relatedTableOwnerKey = $relatedTable . '.' . $ownerKey;

                    // Add JOIN only if it hasn't been added already
                    $joinAlreadyExists = collect($query->toBase()->joins ?? [])->contains(function ($join) use (
                        $relatedTable
                    ) {
                        return $join->table === $relatedTable;
                    });

                    if (! $joinAlreadyExists) {
                        $query->leftJoin($relatedTable, $localTableForeignKey, '=', $relatedTableOwnerKey);

                        // Ensure we only select columns from the main table to avoid column conflicts
                        // Only set select if it hasn't been set already
                        if (empty($query->getQuery()->columns)) {
                            $mainTable = $this->repository->model()->getTable();
                            $query->select([$mainTable . '.*']);
                        }
                    }
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
