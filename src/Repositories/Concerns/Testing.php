<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;

/**
 * Trait Testing
 *
 * @mixin Repository
 *
 * @package Binaryk\LaravelRestify\Repositories\Concerns
 */
trait Testing
{
    public static function to(string $path = null, array $query = []): string
    {
        $base = Restify::path().'/'.static::uriKey();

        $route = $path
            ? $base.'/'.$path
            : $base;

        return $route.'?'.http_build_query($query);
    }

    /**
     * @param  Action  $action
     */
    public static function action(string $action, string|int $key = null): string
    {
        $path = $key ? "$key/actions" : 'actions';

        return static::to($path, [
            'action' => app($action)->uriKey(),
        ]);
    }

    public function dd(string $prop = null): void
    {
        if (is_null($prop)) {
            dd($this);
        }

        dd($this->{$prop});
    }
}
