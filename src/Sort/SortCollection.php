<?php

namespace Binaryk\LaravelRestify\Sort;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Exception;
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

    public function allowed(RestifyRequest $request, Repository $repository)
    {
        $collection = static::make($repository::getOrderByFields());

        return $this->filter(fn (SortableFilter $filter) => $collection->contains('column', '=', $filter->column));
    }

    public function hydrateDefinition(Repository $repository): SortCollection
    {
        return $this->map(function (SortableFilter $filter) use ($repository) {
            if (! array_key_exists($filter->column, $repository::getOrderByFields())) {
                return $filter;
            }

            $definition = Arr::get($repository::getOrderByFields(), $filter->getColumn());

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

    public function normalize()
    {
        return $this->each(fn (SortableFilter $filter) => $filter->syncDirection());
    }
}
