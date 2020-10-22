<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;

class RepositoryRelatableController extends RepositoryController
{
    public function __invoke(RepositoryIndexRequest $request)
    {
        try {
            return $request->newRepository()->index($request);
        } catch (EntityNotFoundException $e) {
            return $this->response()->notFound()
                ->addError($e->getMessage())
                ->dump($e, $request->isDev());
        } catch (UnauthorizedException $e) {
            return $this->response()->forbidden()->addError($e->getMessage())->dump($e, $request->isDev());
        }
    }
}
