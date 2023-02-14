<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class MatchesCollection extends Collection
{
    public function __construct($items = [])
    {
        $unified = [];

        foreach ($items as $column => $matchType) {
            $definition = $matchType instanceof MatchFilter
                ? $matchType
                : tap(
                    MatchFilter::make(),
                    fn (MatchFilter $filter) => is_string($matchType) ? $filter->setType($matchType) : ''
                );

            if ($matchType instanceof Closure) {
                $definition->usingClosure($matchType);
            }

            $definition->setColumn(
                $definition->column ?? $column
            );

            $definition->setType(
                $definition->type ?? (is_string($matchType) ? $matchType : 'int')
            );

            $unified[] = $definition;
        }

        parent::__construct($unified);
    }

    public function hydrateRepository(Repository $repository): self
    {
        return $this->each(fn (Filter $filter) => $filter->setRepository($repository));
    }

    public function inQuery(RestifyRequest $request): self
    {
        return $this->filter(function (MatchFilter $filter) use ($request) {
            $possibleKeys = collect([
                $filter->column(),
                "-{$filter->column()}",
            ]);

            if ($filters = collect($request->input('filter', []))) {
                if ($filters->keys()->intersect($possibleKeys)->count()) {
                    return true;
                }
            }

            return ! is_null($request->query("-{$filter->column()}"))
                || ! is_null($request->query($filter->column()));
        });
    }

    public function authorized(RestifyRequest $request): self
    {
        return $this->filter(fn (MatchFilter $filter) => $filter->authorizedToSee($request));
    }

    public function hydrateDefinition(RestifyRequest $request, Repository $repository): MatchesCollection
    {
        return $this->each(function (MatchFilter $filter) use ($repository, $request) {
            if ($request->has('-'.$filter->getColumn())) {
                $filter->negate();
            }

            if (data_get($request->input('filter'), '-'.$filter->getColumn())) {
                $filter->negate();
            }

            return $filter->setColumn($repository->model()->qualifyColumn($filter->getColumn()));
        });
    }

    public function normalize(): self
    {
        return $this;
    }

    /**
     * @param  Builder|Relation  $builder
     * @return $this
     */
    public function apply(RestifyRequest $request, $builder): self
    {
        return $this->each(function (MatchFilter $filter) use ($request, $builder) {
            $queryValue = $request->input(
                $key = $filter->negation ? '-'.$filter->getQueryKey() : $filter->getQueryKey(),
                data_get($request->input('filter'), $key)
            );

            $filter->filter($request, $builder, $queryValue);
        });
    }
}
