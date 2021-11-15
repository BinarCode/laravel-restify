<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Symfony\Component\HttpFoundation\Response;

class RepositoryIndexController extends RepositoryController
{
    public function __invoke(RepositoryIndexRequest $request): Response
    {
        return $request->repository()->index($request);
    }
}
