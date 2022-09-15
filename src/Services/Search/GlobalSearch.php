<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class GlobalSearch
{
    /**
     * The request instance.
     *
     * @var RestifyRequest
     */
    public $request;

    /**
     * The repository class names that should be searched.
     *
     * @var Collection
     */
    public $repositories;

    public function __construct(RestifyRequest $request, $repositories)
    {
        $this->request = $request;
        $this->repositories = $repositories;
    }

    /**
     * Get the matching repositories.
     *
     * @return array
     */
    public function get()
    {
        $formatted = [];

        /**
         * @var Repository $repository
         */
        foreach ($this->getSearchResults() as $repository => $models) {
            foreach ($models as $model) {
                $instance = $repository::resolveWith($model);

                $formatted[] = [
                    'repositoryName' => $repository::uriKey(),
                    'repositoryTitle' => $repository::label(),
                    'title' => $instance->title(),
                    'subTitle' => $instance->subtitle(),
                    'repositoryId' => $model->getKey(),
                    'link' => $repository::route($model),
                ];
            }
        }

        return $formatted;
    }

    /**
     * Get the search results for the repositories.
     *
     * @return array
     */
    protected function getSearchResults()
    {
        $results = [];

        foreach ($this->repositories as $repository) {
            $query = RepositorySearchService::make()->search($this->request, $repository::resolveWith($repository::newModel()));

            if (count($models = $query->limit($repository::$globalSearchResults)->get()) > 0) {
                $results[$repository] = $models;
            }
        }

        return collect($results)->sortKeys()->all();
    }
}
