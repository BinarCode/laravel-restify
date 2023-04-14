<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;

trait ResolvesActions
{
    public function availableActions(ActionRequest $request)
    {
        $actions = $request->isForRepositoryRequest()
            ? $this->resolveShowActions($request)
            : $this->resolveIndexActions($request);

        return $actions->filter->authorizedToSee($request)
            ->merge($this->resolveInvokableActions($request))
            ->values();
    }

    public function resolveInvokableActions(ActionRequest $request): Collection
    {
        return $this->resolveActions($request)
            ->filter(fn ($action) => is_callable($action))
            ->values();
    }

    public function resolveIndexActions(ActionRequest $request): Collection
    {
        return $this->resolveActions($request)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn ($action) => $action->isShownOnIndex(
                $request,
                $request->repository()
            ))->values();
    }

    public function resolveShowActions(ActionRequest $request): Collection
    {
        return $this->resolveActions($request)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn ($action) => $action->isShownOnShow(
                $request,
                $request->repositoryWith(
                    $request->findModelOrFail()
                )
            ))->values();
    }

    public function resolveActions(RestifyRequest $request): Collection
    {
        return collect(array_values($this->filter($this->actions($request))));
    }

    public function actions(RestifyRequest $request): array
    {
        return [];
    }
}
