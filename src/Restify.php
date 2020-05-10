<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Binaryk\LaravelRestify\Events\RestifyStarting;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\AuthorizesRequests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class Restify
{
    use AuthorizesRequests;

    /**
     * The registered repository names.
     *
     * @var array
     */
    public static $repositories = [];

    /**
     * The callback used to report Restify's exceptions.
     *
     * @var \Closure
     */
    public static $reportCallback;

    /**
     * The callback used to render Restify's exceptions.
     *
     * @var \Closure
     */
    public static $renderCallback;

    /**
     * Get the repository class name for a given key.
     *
     * @param string $key
     * @return string
     */
    public static function repositoryForKey($key)
    {
        return collect(static::$repositories)->first(function ($value) use ($key) {
            return $value::uriKey() === $key;
        });
    }

    /**
     * Get the repository class name for a given key.
     *
     * @param string $model
     * @return string
     */
    public static function repositoryForModel($model)
    {
        return collect(static::$repositories)->first(function ($value) use ($model) {
            if ($model instanceof Model) {
                $model = get_class($model);
            }

            return $value::$model === $model;
        });
    }

    /**
     * Register the given repositories.
     *
     * @param array $repositories
     * @return static
     */
    public static function repositories(array $repositories)
    {
        static::$repositories = array_unique(
            array_merge(static::$repositories, $repositories)
        );

        return new static;
    }

    /**
     * Register all of the repository classes in the given directory.
     *
     * @param string $directory
     * @return void
     * @throws \ReflectionException
     */
    public static function repositoriesFrom($directory)
    {
        $namespace = app()->getNamespace();

        $repositories = [];

        foreach ((new Finder)->in($directory)->files() as $repository) {
            $repository = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($repository->getPathname(), app_path().DIRECTORY_SEPARATOR)
                );

            if (is_subclass_of($repository, Repository::class) && (new ReflectionClass($repository))->isInstantiable()) {
                $repositories[] = $repository;
            }
        }

        static::repositories(
            collect($repositories)->sort()->all()
        );
    }

    /**
     * Get the URI path prefix utilized by Restify.
     *
     * @param null $plus
     * @return string
     */
    public static function path($plus = null)
    {
        if (isset($plus)) {
            return config('restify.base', '/restify-api').'/'.$plus;
        } else {
            return config('restify.base', '/restify-api');
        }
    }

    /**
     * Register an event listener for the Restify "serving" event.
     *
     * This listener is added in the RestifyApplicationServiceProvider
     *
     * @param \Closure|string $callback
     * @return void
     */
    public static function starting($callback)
    {
        Event::listen(RestifyStarting::class, $callback);
    }

    /**
     * @param \Closure|string $callback
     */
    public static function beforeEach($callback)
    {
        Event::listen(RestifyBeforeEach::class, $callback);
    }

    /**
     * Set the callback used for intercepting any request exception.
     *
     * @param \Closure|string $callback
     */
    public static function exceptionHandler($callback)
    {
        static::$renderCallback = $callback;
    }

    public static function globallySearchableRepositories(RestifyRequest $request)
    {
        return collect(static::$repositories)
            ->filter(fn ($repository) => $repository::authorizedToUseRepository($request))
            ->filter(fn ($repository) => $repository::$globallySearchable)
            ->sortBy(static::sortResourcesWith())
            ->all();
    }

    public static function sortResourcesWith()
    {
        return function ($resource) {
            return $resource::label();
        };
    }
}
