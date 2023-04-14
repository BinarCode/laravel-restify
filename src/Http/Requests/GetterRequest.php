<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetterRequest extends RestifyRequest
{
    protected function availableGetters(): Collection
    {
        return collect($this->repository()->availableGetters($this));
    }

    public function getter()
    {
        return once(function () {
            return $this->availableGetters()->first(function ($getter) {
                $uriKey = Getter::guessUriKey($getter);

                return $this->route('getter')
                    ? $this->route('getter') === $uriKey
                    : $this->query('getter') === $uriKey;
            }) ?: abort(
                $this->getterExists() ? 403 : 404,
                'Getter does not exists or you don\'t have enough permissions to perform it.'
            );
        });
    }

    protected function getterExists(): bool
    {
        return $this->availableGetters()->contains(function (mixed $getter) {
            return Getter::guessUriKey($getter) === $this->route('getter') ?? $this->query('getter');
        });
    }

    public function builder(Getter $getter, int $size): Builder
    {
        return tap(
            RepositorySearchService::make()->search($this, $this->repository()),
            function ($query) use ($getter) {
                $getter::indexQuery($this, $query);
            }
        )
            ->when($this->input('repositories') !== 'all', function ($query) {
                $query->whereKey($this->input('repositories', []));
            })
            ->latest($this->model()->getKeyName());
    }

    public function collectRepositories(Getter $getter, $count, Closure $callback): array
    {
        $output = [];

        if (($query = $this->builder($getter, $count))->count() === 0) {
            $output[] = $callback(Collection::make([]));
        }

        $query->chunk($count, function ($chunk) use ($callback, &$output) {
            $output[] = $callback(Collection::make($chunk));
        });

        return $output;
    }

    public function isForRepositoryRequest(): bool
    {
        return $this instanceof RepositoryGetterRequest;
    }
}
