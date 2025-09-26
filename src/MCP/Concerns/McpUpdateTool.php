<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpUpdateTool
{
    public function updateTool(McpUpdateRequest $request): array
    {
        // if missing id throw an Validation Exception
        throw_unless($request->input('id'), ValidationException::withMessages([
            'id' => ['The id field is required.'],
        ]));

        $request->merge([
            'mcp_repository_key' => static::uriKey(),
        ]);

        $model = $request->modelQuery(
            $id = $request->input('id'),
        )->lockForUpdate()->firstOrFail();

        $this->withResource($model);

        return $this->allowToUpdate($request)
            ->update($request, $id)
            ->getData(true);
    }

    public static function updateToolSchema(JsonSchema $schema): array
    {
        $repository = static::resolveWith(static::newModel());
        $request = app(McpUpdateRequest::class);

        $modelName = class_basename(static::guessModelClassName());

        $properties = [
            'id' => $schema->string()->description("The ID of the $modelName to update")->required(),
        ];

        // Use MCP-specific fields when available
        $fields = method_exists($repository, 'fieldsForMcpUpdate')
            ? collect($repository->fieldsForMcpUpdate($request))
            : $repository->collectFields($request)
                ->forUpdate($request, $repository)
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
