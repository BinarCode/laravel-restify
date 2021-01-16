<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

interface Resolvable
{
    public function resolve(RestifyRequest $request, Repository $repository): self;
}
