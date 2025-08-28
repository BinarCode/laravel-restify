<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpUpdateTool
{
    public function updateTool(array $arguments, McpRequest $request): array
    {
        $id = $arguments['id'] ?? null;
        unset($arguments['id']);
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        $model = static::query($request)->findOrFail($id);

        return static::resolveWith($model)->update($request, $id);
    }

    public static function updateToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();
        $modelName = class_basename(static::guessModelClassName());

        $schema->string('id')
            ->description("The ID of the $modelName to update")
            ->required();

        // Add field schemas for update operation
        collect(static::newModel()->getFillable())
            ->each(function ($attribute) use ($schema) {
                $schema->string($attribute)
                    ->description("Update the $attribute field");
            });
    }
}
