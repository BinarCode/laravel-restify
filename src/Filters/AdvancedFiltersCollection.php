<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;

class AdvancedFiltersCollection extends Collection
{
    public function authorized(RestifyRequest $request): self
    {
        return $this->filter(fn (Filter $filter) => $filter->authorizedToSee($request))->values();
    }

    public function inQuery(RestifyRequest $request): self
    {
        $filters = json_decode(base64_decode($request->query('filters')), true);

        return $this->filter(function (AdvancedFilter $filter) use ($filters) {
            return collect($filters)->contains('key', $filter::uriKey());
        });
    }

    public function resolve(RestifyRequest $request): self
    {
        return $this
            ->inQuery($request)
            ->each(function (AdvancedFilter $filter) use ($request) {
                $queryFilter = AdvancedFilterPayloadDto::makeFromRequest($request, $filter::uriKey());

                $filter->validatePayload($request, $queryFilter->value())
                    ->resolve($request, $queryFilter);
            });
    }

    public function apply(RestifyRequest $request, $query): self
    {
        return $this->each(fn (AdvancedFilter $filter) => $filter->filter($request, $query, $filter->value->input()));
    }
}
