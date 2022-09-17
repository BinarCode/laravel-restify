<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonSerializable;

class Serializer implements JsonSerializable, Responsable
{
    use ConditionallyLoadsAttributes;

    protected ?Collection $items = null;

    protected int $perPage = RestifySearchable::DEFAULT_PER_PAGE;

    public function __construct(
        private Repository $repository,
        private array $related = [],
        private ?SortableFilter $sort = null,
        private ?array $meta = [],
    ) {
        $this->perPage = ($this->repository)::$defaultPerPage;
    }

    public function repository(Repository $class): self
    {
        $this->repository = $class;

        return $this;
    }

    public function related(...$related): self
    {
        $this->related = collect(Arr::wrap($related))->flatten()->all();

        return $this;
    }

    public function sort(SortableFilter $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function sortAsc(string $column): self
    {
        return $this->sort(SortableFilter::make()->setColumn($column)->asc());
    }

    public function sortDesc(string $column): self
    {
        return $this->sort(SortableFilter::make()->setColumn($column)->desc());
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function indexMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function model(Model $model): self
    {
        $this->repository = $this->repository::resolveWith($model);

        return $this;
    }

    public function models(Collection $models): self
    {
        if ($models->count() === 1) {
            return $this->model($models->first());
        }

        $this->items = $models
            ->filter(fn ($model) => $model instanceof Model)
            ->map(fn (Model $value) => $this->repository::resolveWith($value));

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        if (is_null($this->items) || $this->items->count() === 1) {
            return tap($this->repository->serializeForShow(
                $this->request(RepositoryShowRequest::class)
            ), fn (array &$data) => $data['meta'] = array_merge($data['meta'] ?? [], $this->meta));
        }

        $paginator = new Paginator($this->items->values(), $this->perPage);

        if (! $this->hasCustomRepository()) {
            return ['data' => $paginator->getCollection()];
        }

        $request = $this->request(RepositoryIndexRequest::class);
        $items = $paginator->getCollection();

        return $this->filter([
            'meta' => $this->meta ?: RepositoryCollection::meta($paginator->toArray()),
            'links' => array_merge(RepositoryCollection::paginationLinks($paginator->toArray()), [
                'filters' => Restify::path($this->repository::uriKey().'/filters'),
            ]),
            'data' => $items
                ->when(
                    $this->sort && $this->sort->direction() === 'desc',
                    fn (Collection $items) => $items->sortByDesc($this->sort->column())
                )
                ->when(
                    $this->sort && $this->sort->direction() === 'asc',
                    fn (Collection $items) => $items->sortBy($this->sort->column())
                )
                ->map(fn (Repository $repository) => $repository->serializeForIndex($request)),
        ]);
    }

    private function request(string $class = null): RestifyRequest
    {
        /**
         * @var RestifyRequest $request
         */
        $request = app($class ?? RestifyRequest::class);

        $request->merge([
            'related' => implode(',', $this->related),
        ]);

        return $request;
    }

    public function toResponse($request)
    {
        return $this;
    }

    private function hasCustomRepository(): bool
    {
        return get_class($this->repository) !== Repository::class;
    }
}
