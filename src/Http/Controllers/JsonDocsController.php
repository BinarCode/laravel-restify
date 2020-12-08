<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;

class JsonDocsController extends RepositoryController
{
    public function __invoke(RestifyRequest $request)
    {
        collect(Restify::$repositories)->each(function ($repository) {
        });
    }
}
