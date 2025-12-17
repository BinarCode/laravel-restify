<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpShowTool
{
    public function showTool(McpShowRequest $request): array
    {
        $id = $request->input('id');

        // Build the query following the same pattern as RepositoryShowController
        $query = static::query($request);

        // Apply showQuery and mainQuery with proper relationship loading
        $model = tap($query, fn ($query) => static::showQuery(
            $request,
            static::mainQuery($request, $query->with(static::collectWiths(
                $request, $this
            )->all()))
        ))->findOrFail($id);

        // Set the model on the repository instance and authorize
        $repository = static::resolveWith($model);
        $repository->allowToShow($request);

        // Use the same serialization method as the controller
        return $repository->serializeForShow($request);
    }

    public static function showToolSchema(JsonSchema $schema): array
    {
        $modelName = class_basename(static::guessModelClassName());

        return [
            'id' => $schema->string()
                ->description("The ID of the $modelName to retrieve")
                ->required(),
            'include' => $schema->string()
                ->description(static::formatRelationshipDocumentation(app(McpShowRequest::class))),
        ];
    }
}
