<?php

namespace Binaryk\LaravelRestify\Repositories;

trait RepositoryEvents
{
    /**
     * The array of booted repositories.
     */
    protected static array $booted = [];

    /**
     * Perform any actions required before the repository boots.
     */
    protected static function booting(): void
    {
        //
    }

    /**
     * Boot the repository.
     */
    protected static function boot(): void
    {
        //
    }

    /**
     * Perform any actions required after the repository boots.
     */
    protected static function booted(): void
    {
        //
    }

    protected function bootIfNotBooted(): void
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            static::booting();
            static::boot();
            static::booted();
        }
    }

    public static function mounting(): void
    {
        if (static::$prefix) {
            static::setPrefix(static::$prefix, static::uriKey());
        }
    }

    /**
     * Clear the list of booted repositories, so they will be re-booted.
     */
    public static function clearBootedRepositories(): void
    {
        static::$booted = [];
    }
}
