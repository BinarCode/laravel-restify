<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Could be used as a trait in a model class and in a repository class.
 *
 * @property Model $resource
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait AuthorizableModels
{
    /**
     * Determine if the given resource is authorizable.
     *
     * @return bool
     */
    public static function authorizable()
    {
        return ! is_null(Gate::getPolicyFor(static::newModel()));
    }

    /**
     * Determine if the Restify is enabled for this repository.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     * @throws AuthorizationException
     */
    public function authorizeToUseRepository(Request $request)
    {
        if (! static::authorizable()) {
            return;
        }

        if (method_exists(Gate::getPolicyFor(static::newModel()), 'allowRestify')) {
            $this->authorizeTo($request, 'allowRestify');
        }
    }

    /**
     * Determine if the resource should be available for the given request.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public static function authorizedToUseRepository(Request $request)
    {
        if (! static::authorizable()) {
            return true;
        }

        return method_exists(Gate::getPolicyFor(static::newModel()), 'allowRestify')
            ? Gate::check('allowRestify', get_class(static::newModel()))
            : true;
    }

    /**
     * Determine if the current user can view the given resource or throw.
     *
     * @param Request $request
     * @throws AuthorizationException
     */
    public function authorizeToShow(Request $request)
    {
        $this->authorizeTo($request, 'show');
    }

    /**
     * Determine if the current user can view the given resource.
     *
     * @param Request $request
     * @return bool
     */
    public function authorizedToShow(Request $request)
    {
        return $this->authorizedTo($request, 'show');
    }

    /**
     * Determine if the current user can store new repositories or throw an exception.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function authorizeToStore(Request $request)
    {
        if (! static::authorizedToStore($request)) {
            throw new AuthorizationException('Unauthorized to store.');
        }
    }

    public static function authorizeToStoreBulk(Request $request)
    {
        if (! static::authorizedToStoreBulk($request)) {
            throw new AuthorizationException('Unauthorized to store bulk.');
        }
    }

    /**
     * Determine if the current user can store new repositories.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public static function authorizedToStore(Request $request)
    {
        if (static::authorizable()) {
            return Gate::check('store', static::$model);
        }

        return true;
    }

    public static function authorizedToStoreBulk(Request $request)
    {
        if (static::authorizable()) {
            return Gate::check('storeBulk', static::$model);
        }

        return true;
    }

    /**
     * Determine if the current user can update the given resource or throw an exception.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToUpdate(Request $request)
    {
        $this->authorizeTo($request, 'update');
    }

    public function authorizeToUpdateBulk(Request $request)
    {
        $this->authorizeTo($request, 'updateBulk');
    }

    /**
     * Determine if the current user can update the given resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
        return $this->authorizedTo($request, 'update');
    }

    /**
     * Determine if the current user can delete the given resource or throw an exception.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToDelete(Request $request)
    {
        $this->authorizeTo($request, 'delete');
    }

    /**
     * Determine if the current user can delete the given resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function authorizedToDelete(Request $request)
    {
        return $this->authorizedTo($request, 'delete');
    }

    /**
     * Determine if the current user has a given ability.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $ability
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeTo(Request $request, $ability)
    {
        if ($this->authorizedTo($request, $ability) === false) {
            throw new AuthorizationException();
        }
    }

    /**
     * Determine if the current user can view the given resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $ability
     * @return bool
     */
    public function authorizedTo(Request $request, $ability)
    {
        return static::authorizable() ? Gate::check($ability, $this->resource) : true;
    }

    /**
     * Determine if the trait is used by repository or model.
     *
     * @return bool
     */
    public static function isRepositoryContext()
    {
        return new static instanceof Repository;
    }
}
