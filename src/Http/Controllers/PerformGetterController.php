<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\GetterRequest;

class PerformGetterController extends RepositoryController
{
    public function __invoke(GetterRequest $request)
    {
        $getter = $request->getter();

        if (is_callable($getter)) {
            return $getter($request);
        }

        return $getter?->handleRequest(
            $request,
        );
    }
}
