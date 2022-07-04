<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Support\Str;

/**
 * Trait Testing
 *
 * @mixin Repository
 *
 * @package Binaryk\LaravelRestify\Repositories\Concerns
 */
trait Testing
{
    public static function route(string $path = null, array $query = []): string
    {
        $base = Str::replaceFirst('//', '/', Restify::path().'/'.static::uriKey());

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

        return static::route($path, [
            'action' => app($action)->uriKey(),
        ]);
    }

    public static function getter(string $getter, string|int $key = null): string
    {
        $path = $key ? "$key/getters" : 'getters';

        return static::route($path.'/'.app($getter)->uriKey());
    }

    public function dd(string $prop = null): void
    {
        if (is_null($prop)) {
            dd($this);
        }

        dd($this->{$prop});
    }
}
