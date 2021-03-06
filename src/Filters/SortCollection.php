<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SortCollection extends Collection
{
    public function __construct($items = [])
    {
        $unified = [];

        foreach ($items as $key => $item) {
            $queryKey = is_numeric($key) ? $item : $key;
            $definition = $item instanceof Filter
                ? $item
                : SortableFilter::make();

            if ($queryKey instanceof SortableFilter) {
                $unified[] = $queryKey;
                continue;
            }

            $definition->setColumn(
                $definition->column ?? $queryKey
            );

            $unified[] = $definition;
        }

        parent::__construct($unified);
    }

    public function hydrateRepository(Repository $repository): self
    {
        return $this->each(fn (Filter $filter) => $filter->setRepository($repository));
    }

    public function inRepository(RestifyRequest $request, Repository $repository): self
    {
        $collection = static::make($repository::sorts());

        return $this->filter(fn (SortableFilter $filter) => $collection->contains('column', '=', $filter->column));
    }

    public function authorized(RestifyRequest $request): self
    {
        return $this->filter(fn (SortableFilter $filter) => $filter->authorizedToSee($request));
    }

    public function hydrateDefinition(Repository $repository): SortCollection
    {
        return $this->map(function (SortableFilter $filter) use ($repository) {
            if (! array_key_exists($filter->column, $repository::sorts())) {
                return $filter;
            }

            $definition = Arr::get($repository::sorts(), $filter->getColumn());

            if (is_callable($definition)) {
                return $filter->usingClosure($definition);
            }

            if ($definition instanceof SortableFilter) {
                return $definition->syncDirection($filter->direction());
            }

            throw new Exception("Invalid argument to {$filter->column} sort in repository.");
        });
    }

    public function forEager(RestifyRequest $request): self
    {
        return $this->filter(fn (SortableFilter $filter) => $filter->hasEager())
            ->unique('column');
    }

    public function normalize(): self
    {
        return $this->each(fn (SortableFilter $filter) => $filter->syncDirection());
    }

    public function apply(RestifyRequest $request, Builder $builder): self
    {
        return $this->each(function (SortableFilter $filter) use ($request, $builder) {
            $filter->filter($request, $builder, $filter->direction());
        });
    }
}
