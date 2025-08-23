<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
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
            ->description(static::formatRelationshipDocumentation());

        $searchableFields = (new SearchablesCollection(static::searchables()))->formatForDocumentation();
        $schema->string('search')
            ->description("Search term to filter $key by name or description. Available searchable fields: {$searchableFields} (e.g., search=term)");

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
            ->description(static::formatRelationshipDocumentation());
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

    /**
     * Get available fields for a specific relationship repository.
     * Uses MCP-specific field methods when available to provide accurate field documentation.
     */
    protected static function getRelationshipFields(string $repositoryClass): array
    {
        try {
            /**
             * @var Repository $repository
             */
            $repository = app($repositoryClass);
            $request = app(McpRequest::class);

            // Get fields for the related repository using MCP-aware field collection
            // This will call fieldsForMcpIndex if available, providing accurate MCP field documentation
            $fields = $repository->collectFields($request)
                ->forMcpIndex($request, $repository)
                ->map(fn ($field) => $field->attribute)
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return $fields;
        } catch (\Exception $e) {
            // Fallback to basic fields if there's an error
            return ['id'];
        }
    }

    /**
     * Format comprehensive relationship documentation including field selection.
     */
    protected static function formatRelationshipDocumentation(): string
    {
        $related = static::collectRelated()->intoAssoc();

        if ($related->isEmpty()) {
            return 'No relationships available for this resource.';
        }

        $documentation = "Comma-separated list of relationships to include with optional field selection.\n\nAvailable relationships:\n";
        $relationshipClasses = [];

        foreach ($related as $relationName => $relationConfig) {
            $repositoryClass = static::extractRepositoryClass($relationConfig);

            if ($repositoryClass) {
                $fields = static::getRelationshipFields($repositoryClass);
                $fieldsList = implode(', ', $fields);
                $documentation .= "- {$relationName} (fields: {$fieldsList})\n";
                $relationshipClasses[$relationName] = $repositoryClass;
            } else {
                $documentation .= "- {$relationName}\n";
            }
        }

        // Generate examples using the first valid relationship
        $firstRelationName = array_key_first($relationshipClasses);
        $firstRepositoryClass = $relationshipClasses[$firstRelationName] ?? null;

        $relationNames = $related->keys()->take(2)->implode(',');

        $documentation .= "\nField Selection:\n";
        $documentation .= "You can specify which fields to include for each relationship using square brackets.\n";
        $documentation .= "Syntax: relationship[field1,field2] or relationship[field1|field2] (both work)\n\n";

        $documentation .= "Nested Relationships:\n";
        $documentation .= "You can include deeply nested relationships using dot notation with field selection at each level.\n";
        $documentation .= "Syntax: relationship[fields].nested[fields].deeper[fields] - supports unlimited nesting depth\n";
        $documentation .= "Note: Field selection works at every nesting level independently.\n\n";

        $documentation .= "Examples:\n";
        $documentation .= "- include={$relationNames} (include all fields)\n";

        if ($firstRelationName && $firstRepositoryClass) {
            $firstFields = static::getRelationshipFields($firstRepositoryClass);
            $exampleFields = array_slice($firstFields, 0, 2);

            if (! empty($exampleFields)) {
                $documentation .= "- include={$firstRelationName}[".implode(',', $exampleFields)."] (selective fields with comma syntax)\n";
                $documentation .= "- include={$firstRelationName}[".implode('|', $exampleFields)."] (selective fields with pipe syntax)\n";
            }

            // Add comprehensive nested relationship examples based on real test cases
            $documentation .= "- include={$firstRelationName}.posts (nested relationship - all fields)\n";
            $documentation .= "- include={$firstRelationName}[name].posts[title] (nested with field selection at each level)\n";
            $documentation .= "- include={$firstRelationName}[name|email].posts[title].tags[id] (deep nesting - 3 levels with field selection)\n";
            $documentation .= "- include={$firstRelationName}.posts[title],{$firstRelationName}.comments[body] (multiple nested from same parent)\n";

            if ($related->count() > 1) {
                $secondRelation = $related->keys()->skip(1)->first();
                $documentation .= "- include={$firstRelationName}[name],{$secondRelation}[id] (multiple relationships with field selection)\n";
                $documentation .= "- include={$firstRelationName}[email|name].posts[title],{$secondRelation}[id] (mixing deep nested and simple relationships)";
            }
        }

        return $documentation;
    }

    /**
     * Extract repository class from relationship configuration.
     */
    protected static function extractRepositoryClass($relationConfig): ?string
    {
        // Handle string repository class
        if (is_string($relationConfig)) {
            return $relationConfig;
        }

        // Handle array configuration with repository key
        if (is_array($relationConfig) && isset($relationConfig['repository'])) {
            return $relationConfig['repository'];
        }

        // Handle Field objects (HasMany, BelongsTo, etc.)
        if (is_object($relationConfig) && method_exists($relationConfig, 'repositoryClass')) {
            return $relationConfig->repositoryClass;
        }

        // Handle Field objects with different property names
        if (is_object($relationConfig) && property_exists($relationConfig, 'repositoryClass')) {
            return $relationConfig->repositoryClass;
        }

        return null;
    }
}
