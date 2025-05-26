<?php

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\RepositoryInstance;
use Binaryk\LaravelRestify\Repositories\Serializer;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

if (! function_exists('field')) {
    function field(...$args): Field
    {
        return Field::new(...$args);
    }
}

if (! function_exists('isRestify')) {
    function isRestify(Request $request): bool
    {
        return Restify::isRestify($request);
    }
}

if (! function_exists('data')) {
    function data(mixed $data = [], int $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status, $headers, $options);
    }
}

if (! function_exists('ok')) {
    function ok(?string $message = null, int $code = 200)
    {
        if (! is_null($message)) {
            return response()->json([
                'message' => $message,
            ], $code);
        }

        return response()->json([], $code);
    }
}

if (! function_exists('id')) {
    function id(): Field
    {
        return field('id')->readonly();
    }
}

if (! function_exists('rest')) {
    function rest(...$models): Serializer
    {
        $models = collect($models)->flatten();

        if ($models->first()) {
            $repository = Restify::repositoryForModel(get_class($models->first())) ?? Repository::class;
        } else {
            $repository = Repository::class;
        }

        return (new Serializer(app($repository)))
            ->models(collect($models));
    }
}

if (! function_exists('currentRepository')) {
    function currentRepository(): Repository
    {
        return app(RepositoryInstance::class)->current();
    }
}


if (! function_exists('belongsTo')) {
    function belongsTo(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\BelongsTo
    {
        return Binaryk\LaravelRestify\Fields\BelongsTo::make($attribute, $repository);
    }
}

if (! function_exists('belongsToMany')) {
    function belongsToMany(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\BelongsToMany
    {
        return Binaryk\LaravelRestify\Fields\BelongsToMany::make($attribute, $repository);
    }
}

if (! function_exists('hasOne')) {
    function hasOne(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\HasOne
    {
        return Binaryk\LaravelRestify\Fields\HasOne::make($attribute, $repository);
    }
}

if (! function_exists('hasMany')) {
    function hasMany(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\HasMany
    {
        return Binaryk\LaravelRestify\Fields\HasMany::make($attribute, $repository);
    }
}

if (! function_exists('morphOne')) {
    function morphOne(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\MorphOne
    {
        return Binaryk\LaravelRestify\Fields\MorphOne::make($attribute, $repository);
    }
}

if (! function_exists('morphMany')) {
    function morphMany(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\MorphMany
    {
        return Binaryk\LaravelRestify\Fields\MorphMany::make($attribute, $repository);
    }
}

if (! function_exists('morphToMany')) {
    function morphToMany(string $attribute, string $repository): Binaryk\LaravelRestify\Fields\MorphToMany
    {
        return Binaryk\LaravelRestify\Fields\MorphToMany::make($attribute, $repository);
    }
}
