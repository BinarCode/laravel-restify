<?php

namespace Binaryk\LaravelRestify\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface RestifyRepositoryInterface
{
    /**
     * @return Builder
     */
    public function query(): Builder;
}
