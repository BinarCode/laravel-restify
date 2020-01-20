<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
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
        try {
            return $request->newRepository()->index($request);
        } catch (EntityNotFoundException $e) {
            return $this->response()->notFound()
                ->addError($e->getMessage())
                ->debug($e, $request->isDev());
        } catch (UnauthorizedException $e) {
            return $this->response()->forbidden()->addError($e->getMessage())->debug($e, $request->isDev());
        } catch (InstanceOfException |\Throwable $e) {
            return $this->response()->error()->debug($e, $request->isDev());
        }
    }
}
