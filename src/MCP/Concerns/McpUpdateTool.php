<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpUpdateTool
{
    public function updateTool(array $arguments, McpUpdateRequest $request): array
    {
        // if missing id throw an Validation Exception
        throw_unless(isset($arguments['id']), ValidationException::withMessages([
            'id' => ['The id field is required.'],
        ]));

        $request->merge($arguments);
        $request->merge([
            'mcp_repository_key' => static::uriKey(),
        ]);

        $this->sanitizeToolRequest($request, $arguments);

        $model = $request->modelQuery(
            $id = data_get($arguments, 'id'),
        )->lockForUpdate()->firstOrFail();

        $this->withResource($model);

        return $this->allowToUpdate($request)
            ->update($request, $id)
            ->getData(true);
    }

    public static function updateToolSchema(ToolInputSchema $schema): void
    {
        $repository = static::resolveWith(static::newModel());

        $modelName = class_basename(static::guessModelClassName());

        $schema->string('id')
            ->description("The ID of the $modelName to update")
            ->required();

        $repository->collectFields($request = app(McpUpdateRequest::class))
            ->forUpdate($request, $repository)
            ->withoutActions($request, $repository)
            ->each(function (Field $field) use ($schema, $repository) {
                $field->resolveToolSchema($schema, $repository);
            });
    }
}
