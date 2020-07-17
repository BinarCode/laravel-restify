<?php


namespace Binaryk\LaravelRestify\Repositories;


trait RepositoryEvents
{
    /**
     * The array of booted repositories.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * Perform any actions required after the repository boots.
     *
     * @return void
     */
    protected static function booted()
    {
        //
    }

    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            static::booted();
        }
    }

    /**
     * Clear the list of booted repositories so they will be re-booted.
     *
     * @return void
     */
    public static function clearBootedRepositories()
    {
        static::$booted = [];
    }
}
