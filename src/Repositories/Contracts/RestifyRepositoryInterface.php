<?php

namespace Binaryk\LaravelRestify\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RestifyRepositoryInterface
{
    /**
     * @return Builder
     */
    public function query(): Builder;

    /**
     * @param array $payload
     * @return Model
     */
    public function store(array $payload);

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection|static[]|static|null
     */
    public function find($id, $columns = ['*']);

    /**
     * @return Model
     */
    public function model();

}
