<?php

namespace Binaryk\LaravelRestify\Repositories;

use ArrayIterator;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryCollection extends Resource
{
    use ResponseResolver;

    /**
     * When the repository is used as a response for a collection list (index controller).
     *
     * @param $request
     * @return array
     */
    public function toArrayForCollection($request)
    {
        $paginated = parent::toArray($request);

        $currentRepository = Restify::repositoryForModel(get_class($this->model()));

        if (is_null($currentRepository)) {
            return Arr::only(parent::toArray($request), 'data');
        }

        $data = collect([]);
        $iterator = $this->iterator();

        while ($iterator->valid()) {
            $data->push($iterator->current());
            $iterator->next();
        }

        $response = $data->map(function ($value) use ($currentRepository) {
            return static::resolveWith($value);
        })->toArray($request);

        return $this->serializeIndex($request, [
            'meta' => $this->when($this->isRenderingPaginated(), $this->meta($paginated)),
            'links' => $this->when($this->isRenderingPaginated(), $this->paginationLinks($paginated)),
            'data' => $response,
        ]);
    }

    /**
     * Get the pagination links for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function paginationLinks($paginated)
    {
        return [
            'first' => $paginated['first_page_url'] ?? null,
            'last' => $paginated['last_page_url'] ?? null,
            'prev' => $paginated['prev_page_url'] ?? null,
            'next' => $paginated['next_page_url'] ?? null,
        ];
    }

    /**
     * Gather the meta data for the response.
     *
     * @param  array  $paginated
     * @return array
     */
    protected function meta($paginated)
    {
        return Arr::except($paginated, [
            'data',
            'first_page_url',
            'last_page_url',
            'prev_page_url',
            'next_page_url',
        ]);
    }

    /**
     * Check if the repository is used as a response for a list of items or for a single
     * model entity.
     * @return bool
     */
    protected function isRenderingRepository()
    {
        return $this->resource instanceof Model;
    }

    /**
     * Check if the repository is used as a response for a list of items or for a single
     * model entity.
     * @return bool
     */
    protected function isRenderingCollection()
    {
        return false === $this->resource instanceof Model;
    }

    /**
     * @return bool
     */
    public function isRenderingPaginated()
    {
        return $this->resource instanceof AbstractPaginator;
    }

    /**
     * If collection or paginator then return model from the first item.
     *
     * @return Model
     */
    protected function modelFromIterator()
    {
        /**
         * @var ArrayIterator
         */
        $iterator = $this->iterator();

        /**
         * This is the first element from the response collection, now we have the class of the restify
         * engine.
         * @var Model
         */
        $model = $iterator->current();

        return $model;
    }

    /**
     * @return ArrayIterator
     */
    protected function iterator()
    {
        return $this->resource->getIterator();
    }
}
