<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class AdvancedFiltersCollection extends Collection
{
    public function authorized(RestifyRequest $request): self
    {
        return $this->filter(fn (Filter $filter) => $filter->authorizedToSee($request))->values();
    }

    public function apply(RestifyRequest $request, $query): self
    {
        return $this->each(fn (AdvancedFilter $filter) => $filter->filter($request, $query, $filter->dataObject->value));
    }

    public static function collectQueryFilters(RestifyRequest $request, Repository $repository): self
    {
        if (! $request->input('filters')) {
            return static::make([]);
        }

        $filters = json_decode(base64_decode($request->input('filters')), true);

        $allowedFilters = $repository->collectAdvancedFilters($request);

        return static::make($filters)
            ->map(function (array $queryFilter) use ($allowedFilters, $request) {
                /** * @var AdvancedFilter $advancedFilter */
                $advancedFilter = $allowedFilters->first(fn (
                    AdvancedFilter $filter
                ) => $filter::uriKey() === data_get($queryFilter, 'key'));

                if (is_null($advancedFilter)) {
                    return null;
                }

                $advancedFilter = clone $advancedFilter;

                $key = data_get($queryFilter, 'key');
                $value = data_get($queryFilter, 'value');
                unset($queryFilter['key'], $queryFilter['value']);

                return $advancedFilter->resolve($request, $dto = new AdvancedFilterPayloadDataObject(
                    $key,
                    $value,
                    $queryFilter,
                ))
                    ->withMeta($queryFilter['meta'] ?? [])
                    ->validatePayload($request, $dto);
            })
            ->filter();
    }
}
