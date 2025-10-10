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

if (! function_exists('datetime')) {
    function datetime(string $attribute = 'created_at'): Field
    {
        return field($attribute)->rules('date');
    }
}

if (! function_exists('textarea')) {
    function textarea(string $attribute = 'description'): Field
    {
        return field($attribute)->rules('string', 'max:65535');
    }
}

if (! function_exists('text')) {
    function text(string $attribute = 'name'): Field
    {
        return field($attribute)->rules('string', 'max:255');
    }
}

if (! function_exists('email')) {
    function email(string $attribute = 'email'): Field
    {
        return field($attribute)->rules('email', 'max:255');
    }
}

if (! function_exists('password')) {
    function password(string $attribute = 'password'): Field
    {
        return field($attribute)->rules('string', 'min:8');
    }
}

if (! function_exists('boolean')) {
    function boolean(string $attribute = 'is_active'): Field
    {
        return field($attribute)->rules('boolean');
    }
}

if (! function_exists('integer')) {
    function integer(string $attribute = 'count'): Field
    {
        return field($attribute)->rules('integer');
    }
}

if (! function_exists('decimal')) {
    function decimal(string $attribute = 'price'): Field
    {
        return field($attribute)->rules('numeric');
    }
}

if (! function_exists('timestamp')) {
    function timestamp(string $attribute = 'created_at'): Field
    {
        return field($attribute)->rules('date')->readonly();
    }
}

if (! function_exists('json')) {
    function json(string $attribute = 'data'): Field
    {
        return field($attribute)->rules('json');
    }
}

if (! function_exists('mcpSchema')) {
    /**
     * Convert Laravel validation rules to MCP JSON Schema.
     *
     * @param  array  $rules  Laravel validation rules
     * @return array Array of Type instances keyed by attribute name
     */
    function mcpSchema(array $rules): array
    {
        $converter = new \Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
        $schema = new \Illuminate\JsonSchema\JsonSchemaTypeFactory;

        return $converter($schema, $rules);
    }
}
