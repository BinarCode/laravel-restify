<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Attributes\Model as ModelAttribute;
use Binaryk\LaravelRestify\Repositories\NullModel;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
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

    /**
     * The primary key of the row of this repository that is the given authenticated user, or null when the user is
     * not a row of it. The profile endpoints and the MCP profile tool read and write that row and no other.
     *
     * An id alone does not say which table it comes from: in an application with several guards, an admin and a
     * user regularly share one. So the user is a row of this repository only when it is an Eloquent model stored
     * where the repository's model is stored: on the same connection, in the same table, under the same primary
     * key column. The class is not compared, as Model::is() does not compare it: a subclass of the repository's
     * model may be stored in another table, and another class may be stored in this one.
     *
     * Override it when the rows of the repository authenticate as something else, such as a user object built
     * from a token, and return the key of the row that user stands for.
     */
    public static function profileKey(?Authenticatable $user): mixed
    {
        if (! $user instanceof Model) {
            return null;
        }

        $model = static::newModel();

        $isRowOfRepository = $user->getTable() === $model->getTable()
            && $user->getKeyName() === $model->getKeyName()
            && $user->getConnection()->getName() === $model->getConnection()->getName();

        return $isRowOfRepository ? $user->getKey() : null;
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
