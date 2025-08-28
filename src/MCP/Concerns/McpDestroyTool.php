<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpDestroyTool
{
    public function deleteTool(array $arguments, McpRequest $request): array
    {
        $id = $arguments['id'] ?? null;
        unset($arguments['id']);
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        $model = static::query($request)->findOrFail($id);

        return static::resolveWith($model)->destroy($request, $id);
    }

    public static function destroyToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();
        $modelName = class_basename(static::guessModelClassName());

        $schema->string('id')
            ->description("The ID of the $modelName to delete")
            ->required();
    }
}
