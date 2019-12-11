<?php

namespace Binaryk\LaravelRestify\Services;

use Binaryk\LaravelRestify\Repositories\Contracts\RestifyRepositoryInterface;

/**
 * @package Binaryk\LaravelRestify\Services;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyService
{
    /**
     * @var RestifyRepositoryInterface
     */
    protected $repository;

    public function __construct(RestifyRepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }
}
