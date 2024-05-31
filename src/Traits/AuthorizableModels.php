<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Cache\PolicyCache;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Could be used as a trait in a model class and in a repository class.
 *
 * @property Model $resource
 *
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait AuthorizableModels
{
    public static function authorizable(): bool
    {
        return ! is_null(Gate::getPolicyFor(static::newModel()));
    }

    public static function authorizedToUseRepository(Request $request): bool
    {
        if (! static::authorizable()) {
            return false;
        }

        $resolver = function () {
            return method_exists(Gate::getPolicyFor(static::newModel()), 'allowRestify')
                ? Gate::check('allowRestify', get_class(static::newModel()))
                : false;
        };

        return PolicyCache::resolve(PolicyCache::keyForAllowRestify(static::uriKey()), $resolver, static::newModel());
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeToShow(Request $request): void
    {
        $this->authorizeTo($request, 'show');
    }

    public function authorizedToShow(Request $request): bool
    {
        return $this->authorizedTo($request, 'show');
    }

    /**
     * @throws AuthorizationException
     */
    public static function authorizeToStore(Request $request): void
    {
        if (! static::authorizedToStore($request)) {
            throw new AuthorizationException('Unauthorized to store.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    public static function authorizeToStoreBulk(Request $request): void
    {
        if (! static::authorizedToStoreBulk($request)) {
            throw new AuthorizationException('Unauthorized to store bulk.');
        }
    }

    public static function authorizedToStore(Request $request): bool
    {
        if (static::authorizable()) {
            return Gate::check('store', static::guessModelClassName());
        }

        return false;
    }

    public static function authorizedToStoreBulk(Request $request): bool
    {
        if (static::authorizable()) {
            return Gate::check('storeBulk', static::guessModelClassName());
        }

        return false;
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeToUpdate(Request $request): void
    {
        $this->authorizeTo($request, 'update');
    }

    public function authorizeToAttach(Request $request, $method, $model): bool
    {
        if (! static::authorizable()) {
            return false;
        }

        $policyClass = get_class(Gate::getPolicyFor($this->model()));

        $authorized = method_exists($policy = Gate::getPolicyFor($this->model()), $method)
            ? Gate::check($method, [$this->model(), $model])
            : abort(403, "Missing method [$method] in your [$policyClass] policy.");

        if ($authorized === false) {
            abort(
                403,
                'You cannot attach model:'.get_class($model).', to the model:'.get_class($this->model()).', check your permissions.'
            );
        }

        return false;
    }

    public function authorizeToSync(Request $request, $method, Collection $keys): bool
    {
        if (! static::authorizable()) {
            return false;
        }

        $policyClass = get_class(Gate::getPolicyFor($this->model()));

        $authorized = method_exists($policy = Gate::getPolicyFor($this->model()), $method)
            ? Gate::check($method, [$this->model(), $keys])
            : abort(403, "Missing method [$method] in your [$policyClass] policy.");

        if ($authorized === false) {
            abort(
                403,
                'You cannot sync key to the model:'.get_class($this->model()).', check your permissions.'
            );
        }

        return false;
    }

    public function authorizeToDetach(Request $request, $method, $model)
    {
        if (! static::authorizable()) {
            throw new AuthorizationException();
        }

        $authorized = method_exists(Gate::getPolicyFor($this->model()), $method)
            ? Gate::check($method, [$this->model(), $model])
            : false;

        if ($authorized === false) {
            throw new AuthorizationException();
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeToUpdateBulk(Request $request): void
    {
        $this->authorizeTo($request, 'updateBulk');
    }

    public function authorizeToDeleteBulk(Request $request)
    {
        $this->authorizeTo($request, 'deleteBulk');
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return $this->authorizedTo($request, 'update');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeToDelete(Request $request): void
    {
        $this->authorizeTo($request, 'delete');
    }

    public function authorizedToDelete(Request $request): bool
    {
        return $this->authorizedTo($request, 'delete');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeTo(Request $request, iterable|string $ability): void
    {
        if ($this->authorizedTo($request, $ability) === false) {
            throw new AuthorizationException();
        }
    }

    public function authorizedTo(Request $request, iterable|string $ability): bool
    {
        if (! static::authorizable()) {
            return false;
        }

        return PolicyCache::resolve(
            PolicyCache::keyForPolicyMethods(static::uriKey(), $ability, $this->resource->getKey()),
            fn () => Gate::check($ability, $this->resource),
            $this->model(),
        );
    }

    public static function isRepositoryContext(): bool
    {
        return new static() instanceof Repository;
    }
}
