<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpShowTool
{
    public function showTool(array $arguments, McpRequest $request): array
    {
        $id = $arguments['id'] ?? null;
        unset($arguments['id']);
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        // Build the query following the same pattern as RepositoryShowController
        $query = static::query($request);

        // Apply showQuery and mainQuery with proper relationship loading
        $model = tap($query, fn ($query) => static::showQuery(
            $request,
            static::mainQuery($request, $query->with(static::withs()))
        ))->with(static::withs())->findOrFail($id);

        // Set the model on the repository instance and authorize
        $repository = static::resolveWith($model);
        $repository->allowToShow($request);

        // Use the same serialization method as the controller
        return $repository->serializeForShow($request);
    }

    public static function showToolSchema(ToolInputSchema $schema): void
    {
        $modelName = class_basename(static::$model);

        $schema->string('id')
            ->description("The ID of the $modelName to retrieve")
            ->required();

        $schema->string('include')
            ->description(static::formatRelationshipDocumentation());
    }
}