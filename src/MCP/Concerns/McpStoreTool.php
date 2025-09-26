<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpStoreTool
{
    public function storeTool(McpStoreRequest $request): array
    {
        return $this
            ->allowToStore($request)
            ->store($request)
            ->getData(true);
    }

    public static function storeToolSchema(JsonSchema $schema): array
    {
        $repository = static::resolveWith(static::newModel());
        $request = app(McpStoreRequest::class);

        $properties = [];

        // Use MCP-specific fields when available
        $fields = method_exists($repository, 'fieldsForMcpStore')
            ? collect($repository->fieldsForMcpStore($request))
            : $repository->collectFields($request)
                ->forStore($request, $repository)
                ->withoutActions($request, $repository);

        $fields->each(function (Field $field) use ($schema, $repository, &$properties) {
            $fieldSchema = $field->resolveJsonSchema($schema, $repository);
            if ($fieldSchema !== null) {
                $properties[$field->attribute] = $fieldSchema;
            }
        });

        return $properties;
    }
}
