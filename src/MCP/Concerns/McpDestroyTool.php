<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Binaryk\LaravelRestify\Repositories\DeleteField;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Repository
 */
trait McpDestroyTool
{
    public function deleteTool(McpDestroyRequest $request): array
    {
        $id = $request->input('id');

        $query = static::query($request);

        if (config('restify.repositories.apply_main_query_to_writes', true)) {
            $query = static::mainQuery($request, $query);
        }

        $model = $query->findOrFail($id);

        $repository = static::resolveWith($model)->allowToDestroy($request);

        DeleteField::pruneFields($request, $repository, $model);

        $repository->destroy($request, $id);

        return [
            'id' => $id,
            'deleted' => true,
        ];
    }

    public static function destroyToolSchema(JsonSchema $schema): array
    {
        $modelName = class_basename(static::guessModelClassName());

        return [
            'id' => $schema->string()
                ->description("The ID of the $modelName to delete")
                ->required(),
        ];
    }
}
