<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Repositories\NullModel;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait InteractsWithModel
 *
 * @mixin Repository
 */
trait InteractsWithModel
{
    /**
     * @return Model
     */
    public function model()
    {
        return $this->resource ?? static::newModel();
    }

    public static function newModel(): Model
    {
        return app(static::guessModelClassName());
    }

    public static function guessModelClassName(): string
    {
        if (property_exists(static::class, 'model')) {
            return static::$model;
        }

        $prefix = Str::singular(
            Str::studly(Str::replaceLast('Repository', '', class_basename(get_called_class())))
        );

        if (class_exists($model = "App\\Models\\{$prefix}")) {
            return $model;
        }

        if (class_exists($model = "App\\$prefix")) {
            return $model;
        }

        $domain = Str::of($prefix)->pluralStudly()->__toString();

        if (class_exists($model = "App\\Domains\\{$domain}\\Models\\{$prefix}")) {
            return $model;
        }

        return NullModel::class;
    }
}
