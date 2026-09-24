<?php

namespace Binaryk\LaravelRestify;

use Binaryk\LaravelRestify\Bootstrap\BootRepository;
use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Binaryk\LaravelRestify\Events\RestifyStarting;
use Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Traits\AuthorizesRequests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class Restify
{
    use AuthorizesRequests;

    /**
     * The registered repository names.
     *
     * @var list<class-string<Repository>>
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
     */
    public static function repositoryClassForKey(string $key): ?string
    {
        foreach (static::$repositories as $repository) {
            if ($repository::uriKey() === $key) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Get the repository class for the prefix.
     */
    public static function repositoryClassForPrefix(string $prefix): ?string
    {
        $trimmedPrefix = ltrim($prefix, '/');

        foreach (static::$repositories as $repository) {
            if (str_contains($trimmedPrefix, ltrim($repository::route(), '/'))) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Return the repository instance for a given key.
     *
     *
     * @throw RepositoryNotFoundException
     */
    public static function repository(string $key): Repository
    {
        /**
         * @var Repository|string $repositoryClass
         */
        if (is_null($repositoryClass = static::repositoryClassForKey($key))) {
            throw RepositoryNotFoundException::make($key);
        }

        return $repositoryClass::isMock()
            ? $repositoryClass::getMock()
            : $repositoryClass::resolveWith($repositoryClass::newModel());
    }

    /**
     * Get the repository class name for a given model.
     *
     * @param  Model|string  $model
     * @return class-string<Repository>|null
     */
    public static function repositoryForModel($model)
    {
        $modelClass = $model instanceof Model ? get_class($model) : $model;

        foreach (static::$repositories as $repository) {
            if ($repository::guessModelClassName() === $modelClass) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Get the repository class name for a given table name.
     *
     * @param  string  $table
     * @return class-string<Repository>|null
     */
    public static function repositoryForTable($table)
    {
        foreach (static::$repositories as $repository) {
            if (app($repository::guessModelClassName())->getTable() === $table) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * Register the given repositories.
     *
     * @param  list<class-string<Repository>>  $repositories
     * @return static
     */
    public static function repositories(array $repositories)
    {
        static::$repositories = array_values(array_unique(
            array_merge(static::$repositories, $repositories)
        ));

        foreach ($repositories as $repository) {
            (new BootRepository($repository))->boot();
        }

        return new static;
    }

    /**
     * Register all repository classes in the given directory and namespace.
     *
     * @throws ReflectionException
     */
    public static function repositoriesFrom(string $directory, string $namespace): void
    {
        $basePath = $namespace === 'App\\' ? app_path() : $directory;
        $repositories = [];

        if (! is_dir($directory)) {
            return;
        }

        foreach ((new Finder)->in($directory)->files() as $repository) {
            $repository = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($repository->getPathname(), $basePath.DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of(
                $repository,
                Repository::class
            ) && (new ReflectionClass($repository))->isInstantiable()) {
                $repositories[] = $repository;
            }
        }

        sort($repositories);

        static::repositories($repositories);
    }

    /**
     * Get the URI path prefix utilized by Restify.
     *
     * @return string
     */
    public static function path($plus = null, array $query = [])
    {
        if (! is_null($plus)) {
            return empty($query)
                ? config('restify.base', '/restify-api').'/'.$plus
                : config('restify.base', '/restify-api').'/'.$plus.'?'.http_build_query($query);
        }

        return empty($query)
            ? config('restify.base', '/restify-api')
            : config('restify.base', '/restify-api').'?'.http_build_query($query);
    }

    /**
     * Register an event listener for the Restify "serving" event.
     *
     * This listener is added in the RestifyApplicationServiceProvider
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function starting($callback)
    {
        Event::listen(RestifyStarting::class, $callback);
    }

    /**
     * @param  \Closure|string  $callback
     */
    public static function beforeEach($callback)
    {
        Event::listen(RestifyBeforeEach::class, $callback);
    }

    /**
     * Set the callback used for intercepting any request exception.
     *
     * @param  \Closure|string  $callback
     */
    public static function exceptionHandler($callback)
    {
        static::$renderCallback = $callback;
    }

    public static function globallySearchableRepositories(RestifyRequest $request): array
    {
        $searchableRepositories = array_filter(
            static::$repositories,
            fn (string $repository): bool => $repository::authorizedToUseRepository($request)
                && $repository::$globallySearchable,
        );

        $sortKey = static::sortResourcesWith();

        // asort() preserves keys, matching Collection::sortBy()'s ordering exactly.
        $labels = array_map(
            static fn (string $repository): mixed => $sortKey($repository),
            $searchableRepositories
        );

        asort($labels);

        $sortedRepositories = [];

        foreach (array_keys($labels) as $key) {
            $sortedRepositories[$key] = $searchableRepositories[$key];
        }

        return $sortedRepositories;
    }

    /**
     * @return \Closure(class-string<Repository>): mixed
     */
    public static function sortResourcesWith()
    {
        return function ($resource) {
            return $resource::label();
        };
    }

    /**
     * Humanize the given value into a proper name.
     *
     * @param  string|object  $value
     * @return string
     */
    public static function humanize($value)
    {
        if (is_object($value)) {
            return static::humanize(class_basename(get_class($value)));
        }

        return Str::title(Str::snake($value, ' '));
    }

    public static function actionLog(): ActionLog
    {
        return static::actionRepository()::newModel();
    }

    public static function actionRepository(): Repository
    {
        return app(config('restify.logs.repository'));
    }

    public static function isRestify(Request $request): bool
    {
        $path = trim(static::path(), '/') ?: '/';

        if ($request->is($path) || $request->is(trim($path.'/*', '/')) || $request->is('restify-api/*')) {
            return true;
        }

        foreach (static::$repositories as $repository) {
            $prefix = $repository::prefix();

            if (in_array($prefix, [null, ''], true)) {
                continue;
            }

            if ($request->is($prefix.'/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ReflectionException
     */
    public static function ensureRepositoriesLoaded(): void
    {
        if (empty(static::$repositories)) {
            static::repositoriesFrom(app_path('Restify'), app()->getNamespace());
        }
    }
}
