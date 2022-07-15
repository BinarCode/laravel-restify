<?php

namespace Binaryk\LaravelRestify\Repositories;

class RepositoryInstance
{
    public function __construct(
        public Repository $repository,
    ) {
    }

    public function current(): Repository
    {
        return $this->repository;
    }
}
