<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Repositories\Contracts\RestifyRepositoryInterface;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyService
{
    /**
     * @var RestifyRepositoryInterface
     */
    public $repository;

    public function __construct(RestifyRepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }
}
