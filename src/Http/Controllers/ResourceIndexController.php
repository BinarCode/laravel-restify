<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Requests\RestifyRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ResourceIndexController extends ResourceController
{
    public function handle(RestifyRequest $request)
    {
        $resource = $request->resource();

        $data = $resource::query()->get();

        return $this->respond($data);
    }
}
