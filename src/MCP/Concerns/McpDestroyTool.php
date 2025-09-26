<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpDestroyTool
{
    public function deleteTool(McpDestroyRequest $request): array
    {
        $id = $request->input('id');

        $model = static::query($request)->findOrFail($id);

        return static::resolveWith($model)->destroy($request, $id);
    }

    public static function destroyToolSchema(JsonSchema $schema): array
    {
        $key = static::uriKey();
        $modelName = class_basename(static::guessModelClassName());

        return [
            'id' => $schema->string()
                ->description("The ID of the $modelName to delete")
                ->required(),
        ];
    }
}
