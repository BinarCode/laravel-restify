<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryIndexController extends RepositoryController
{
    public function handle(RestifyRequest $request)
    {
        $resource = $request->repository();

        $data = $resource::query()->get();

        return $this->respond($data);
    }
}
