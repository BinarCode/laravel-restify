<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesActions
{
    public function availableActions(RestifyRequest $request)
    {
        return $this->resolveActions($request)->filter->authorizedToSee($request)->values();
    }

    public function resolveActions(RestifyRequest $request): Collection
    {
        return collect(array_values($this->filter($this->actions($request))));
    }

    public function actions(RestifyRequest $request)
    {
        return [];
    }
}
