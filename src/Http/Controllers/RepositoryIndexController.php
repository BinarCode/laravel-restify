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
     * @return \Illuminate\Http\JsonResponse
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     * @throws \Binaryk\LaravelRestify\Exceptions\UnauthorizedException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(RestifyRequest $request)
    {
        $resource = $request->repository();

        $data = $this->search($resource::newModel());

        return $this->respond($data);
    }
}
