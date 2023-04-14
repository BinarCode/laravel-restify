<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Collection;

trait ResolvesGetters
{
    public function availableGetters(GetterRequest $request): Collection
    {
        $getters = $request->isForRepositoryRequest()
            ? $this->resolveShowGetters($request)
            : $this->resolveIndexGetters($request);

        return $getters->filter->authorizedToSee($request)
            ->merge($this->resolveInvokableGetters($request))
            ->values();
    }

    public function resolveInvokableGetters(GetterRequest $request): Collection
    {
        return $this->resolveGetters($request)
            ->filter(fn ($getter) => is_callable($getter))
            ->values();
    }

    public function resolveIndexGetters(GetterRequest $request): Collection
    {
        return $this->resolveGetters($request)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn ($getter) => $getter->isShownOnIndex(
                $request,
                $request->repository()
            ))->values();
    }

    public function resolveShowGetters(GetterRequest $request): Collection
    {
        return $this->resolveGetters($request)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn ($getter) => $getter->isShownOnShow(
                $request,
                $request->repositoryWith(
                    $request->findModelOrFail()
                )
            ))->values();
    }

    public function resolveGetters(RestifyRequest $request): Collection
    {
        return collect(array_values($this->filter($this->getters($request))));
    }

    public function getters(RestifyRequest $request): array
    {
        return [];
    }
}
