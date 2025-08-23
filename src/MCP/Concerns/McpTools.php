<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpTools
{
    public function mcpAllowsIndex(): bool
    {
        return true;
    }

    public function mcpAllowsShow(): bool
    {
        return true;
    }

    public function mcpAllowsStore(): bool
    {
        return false;
    }

    public function mcpAllowsUpdate(): bool
    {
        return false;
    }

    public function mcpAllowsDelete(): bool
    {
        return false;
    }

    public function indexTool(array $arguments, McpRequest $request): array
    {
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        return $this->indexAsArray($request);
    }

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

    public function storeTool(array $arguments, McpRequest $request): array
    {
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        return $this->store($request);
    }

    public function updateTool(array $arguments, McpRequest $request): array
    {
        $id = $arguments['id'] ?? null;
        unset($arguments['id']);
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        $model = static::query($request)->findOrFail($id);

        return static::resolveWith($model)->update($request, $id);
    }

    public function deleteTool(array $arguments, McpRequest $request): array
    {
        $id = $arguments['id'] ?? null;
        unset($arguments['id']);
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        $model = static::query($request)->findOrFail($id);

        return static::resolveWith($model)->destroy($request, $id);
    }

    public static function indexToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();

        $schema->number('page')
            ->description('Page number for pagination');

        $schema->number('perPage')
            ->description("Number of $key per page");

        $schema->string('include')
            ->description('Comma-separated list of relationships to include in the response. Available options: '.implode(', ',
                static::collectRelated()
                    ->intoAssoc()
                    ->keys()->toArray()).' (e.g., include=relation1,relation2)');

        $schema->string('search')
            ->description("Search term to filter $key by name or description. Available options: ".implode(', ',
                array_keys(static::searchables())).' (e.g., search=term)');

        $sortOptions = collect(static::sorts())
            ->map(function ($value, $key) {
                if (is_string($value)) {
                    return $value;
                }
                if (is_numeric($key)) {
                    return $value;
                }

                return $key;
            })
            ->values()
            ->toArray();

        $schema->string('sort')
            ->description("Sorting criteria for the $key. Available options: ".implode(', ',
                $sortOptions).' (e.g., sort=field or sort=-field for descending)');

        collect(static::matches())
            ->each(function ($type, $matchFilter) use ($schema, $key) {
                // Skip if the type is an object (like a filter class)
                if (is_object($type)) {
                    return;
                }

                return match ($type) {
                    RestifySearchable::MATCH_INTEGER, 'integer' => $schema->integer($matchFilter)
                        ->description("Filter $key by exact match for $matchFilter (e.g., $matchFilter=some_value)"),
                    RestifySearchable::MATCH_ARRAY, 'array' => $schema->integer($matchFilter)
                        ->description("Filter $key by array of values for $matchFilter (e.g., $matchFilter=1,2,3)"),
                    RestifySearchable::MATCH_BETWEEN, 'between' => $schema->string($matchFilter)
                        ->description("Filter $key by range for $matchFilter (e.g., $matchFilter=1,10)"),
                    RestifySearchable::MATCH_BOOL, 'boolean' => $schema->boolean($matchFilter)
                        ->description("Filter $key by boolean value for $matchFilter (e.g., $matchFilter=true or $matchFilter=false)"),
                    RestifySearchable::MATCH_TEXT, 'text', 'string' => $schema->string($matchFilter)
                        ->description("Filter $key by $matchFilter (e.g., $matchFilter=value)"),
                    default => $schema->string($matchFilter)
                        ->description("Filter $key by $matchFilter (e.g., $matchFilter=value)")
                };
            });
    }

    public static function showToolSchema(ToolInputSchema $schema): void
    {
        $modelName = class_basename(static::$model);

        $schema->string('id')
            ->description("The ID of the $modelName to retrieve")
            ->required();

        $schema->string('include')
            ->description('Comma-separated list of relationships to include. Available: '.implode(', ',
                static::collectRelated()
                    ->intoAssoc()
                    ->keys()->toArray()));
    }

    public static function storeToolSchema(ToolInputSchema $schema): void
    {
        $repository = static::resolveWith(app(static::$model));

        $repository->collectFields($request = app(McpRequest::class))
            ->forStore($request, $repository)
            ->withoutActions($request, $repository)
            ->each(function (Field $field) use ($schema, $repository) {
                $field->resolveToolSchema($schema, $repository);
            });
    }

    public static function updateToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();
        $modelName = class_basename(static::$model);

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

    public static function destroyToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();
        $modelName = class_basename(static::$model);

        $schema->string('id')
            ->description("The ID of the $modelName to delete")
            ->required();
    }

    protected function sanitizeToolRequest(RestifyRequest $request, array $arguments): void
    {
        if (! isset($arguments['id'])) {
            $request->merge([
                'id' => null,
            ]);
        }
    }
}
