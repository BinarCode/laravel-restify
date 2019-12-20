<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class Restify
{
    /**
     * The registered resource names.
     *
     * @var array
     */
    public static $resources = [];

    /**
     * Get the resource class name for a given key.
     *
     * @param  string  $key
     * @return string
     */
    public static function resourceForKey($key)
    {
        return collect(static::$resources)->first(function ($value) use ($key) {
            return $value::uriKey() === $key;
        });
    }

    /**
     * Register the given resources.
     *
     * @param  array  $resources
     * @return static
     */
    public static function resources(array $resources)
    {
        static::$resources = array_unique(
            array_merge(static::$resources, $resources)
        );

        return new static;
    }

    /**
     * Register all of the resource classes in the given directory.
     *
     * @param  string  $directory
     * @return void
     * @throws \ReflectionException
     */
    public static function resourcesFrom($directory)
    {
        $namespace = app()->getNamespace();

        $resources = [];

        foreach ((new Finder)->in($directory)->files() as $resource) {
            $resource = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($resource->getPathname(), app_path().DIRECTORY_SEPARATOR)
                );

            if (is_subclass_of($resource, Resource::class) && (new ReflectionClass($resource))->isInstantiable()) {
                $resources[] = $resource;
            }
        }

        static::resources(
            collect($resources)->sort()->all()
        );
    }

    /**
     * Get the URI path prefix utilized by Nova.
     *
     * @return string
     */
    public static function path()
    {
        return config('restify.base', '/restify');
    }
}
