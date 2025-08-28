<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Attributes\Model as ModelAttribute;
use Binaryk\LaravelRestify\Repositories\NullModel;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;

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
        // First priority: Check for #[Model] attribute
        if ($modelClass = static::getModelFromAttribute()) {
            return $modelClass;
        }

        // Second priority: Check for static $model property
        if (property_exists(static::class, 'model')) {
            return static::$model;
        }

        // Third priority: Auto-guess based on repository class name
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

    protected static function getModelFromAttribute(): ?string
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $attributes = $reflection->getAttributes(ModelAttribute::class);

            if (empty($attributes)) {
                return null;
            }

            /** @var ModelAttribute $modelAttribute */
            $modelAttribute = $attributes[0]->newInstance();

            return $modelAttribute->getModelClass();
        } catch (\ReflectionException) {
            return null;
        }
    }
}
