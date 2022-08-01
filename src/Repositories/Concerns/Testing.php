<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Testing
 *
 * @mixin Repository
 */
trait Testing
{
    public static function route(
        string|Model $path = null,
        Action $action = null,
        array $query = [],
    ): string {
        if ($path instanceof Model) {
            $path = $path->getKey();
        }

        $path = ltrim($path, '/');

        if ($action) {
            $query['action'] = $action->uriKey();
        }

        $route = implode('/', array_filter([
            Restify::path(),
            static::uriKey(),
            $path,
            $action ? 'actions' : null,
        ]));

        if (empty($query)) {
            return $route;
        }

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
