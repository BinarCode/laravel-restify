<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryIndexController extends RepositoryController
{
    /**
     * @param  RestifyRequest  $request
     * @return \Binaryk\LaravelRestify\Repositories\Repository
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     * @throws \Binaryk\LaravelRestify\Exceptions\InstanceOfException
     * @throws \Binaryk\LaravelRestify\Exceptions\UnauthorizedException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Throwable
     */
    public function handle(RestifyRequest $request)
    {
        $data = $this->paginator($request->newRepository());

        return $request->newRepositoryWith($data);
    }
}
