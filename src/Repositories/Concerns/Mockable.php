<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Repositories\Repository;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

trait Mockable
{
    /**
     * The application instance being repository.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    public static $resolvedInstance;

    /**
     * Initiate a partial mock on the facade.
     *
     * @return \Mockery\MockInterface
     */
    public static function partialMock()
    {
        $name = static::uriKey();

        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();

        return $mock->makePartial();
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\MockInterface
     */
    protected static function createFreshMockInstance()
    {
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);

            $mock->shouldAllowMockingProtectedMethods();
        });
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        static::$resolvedInstance[static::uriKey()] = $instance;

        if (isset(static::$app)) {
            static::$app->instance(static::uriKey(), $instance);
        } else {
            app()->instance(static::class, $instance);
        }
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \Mockery\MockInterface
     */
    protected static function createMock()
    {
        $class = static::getMockableClass();

        $mock = Mockery::mock($class);

        // Workaround, because if the `uriKey` is called on a mock object,
        // and the base repository doesn't implement the `uriKey` method
        // it will resolve the kebab name of the mocked object, which is not
        // the same as the original kebab string of the base repository class.
        $mock->shouldReceive('uriKey')
            ->andReturn(static::uriKey());

        return $class
            ? $mock
            : Mockery::mock();
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        return static::class;
    }

    /**
     * Set the application instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function setApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Determines whether a mock is set as the instance of the repository.
     *
     * @return bool
     */
    protected static function isMock()
    {
        $name = static::uriKey();

        return isset(static::$resolvedInstance[$name]) &&
            static::$resolvedInstance[$name] instanceof MockInterface;
    }

    /**
     * Clear a resolved repository instance.
     *
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public static function uriKey()
    {
        throw new RuntimeException('Repository does not implement uriKey method.');
    }

    public static function getMock(): null|MockInterface|Repository
    {
        return static::isMock()
            ? static::$resolvedInstance[static::uriKey()]
            : null;
    }
}
