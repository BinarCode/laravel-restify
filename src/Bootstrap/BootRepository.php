<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Repositories\Repository;

class BootRepository
{
    /** * @var Repository $repository */

    public function __construct(
        private string $repository
    ) {
    }

    public function boot(): void
    {
        (new CustomRoutesBoot([$this->repository]))->boot();

        ($this->repository)::mounting();
    }
}
