<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
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
        array $query = [],
        Action|callable $action = null,
    ): string {
        if ($path instanceof Model) {
            $path = $path->getKey();
        }

        $path = ltrim((string) $path, '/');

        if ($action || is_callable($action)) {
            $query['action'] = Action::guessUriKey($action);
        }

        $route = implode('/', array_filter([
            static::prefix() ?: Restify::path(),
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
        return static::route($key, action: app($action));
    }

    public static function getter(string $getter, string|int $key = null): string
    {
        $path = $key ? "$key/getters" : 'getters';

        return static::route($path.'/'.Getter::guessUriKey(app($getter)));
    }

    public function dd(string $prop = null): void
    {
        if (is_null($prop)) {
            dd($this);
        }

        dd($this->{$prop});
    }
}
