<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryGetterRequest;

class PerformRepositoryGetterController extends RepositoryController
{
    public function __invoke(RepositoryGetterRequest $request)
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
