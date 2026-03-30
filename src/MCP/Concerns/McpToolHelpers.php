<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * @mixin Repository
 */
trait McpToolHelpers
{
    protected static function getRelationshipFields(string $repositoryClass, McpRequest $request): array
    {
        try {
            /**
             * @var Repository $repository
             */
            $repository = app($repositoryClass);

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

    protected static function formatRelationshipDocumentation(McpRequest $request): string
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
                $fields = static::getRelationshipFields($repositoryClass, $request);
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
        $documentation .= "Syntax: relationship[field1|field2]\n\n";

        $documentation .= "Nested Relationships:\n";
        $documentation .= "You can include deeply nested relationships using dot notation with field selection at each level.\n";
        $documentation .= "Syntax: relationship[fields].nested[fields].deeper[fields] - supports unlimited nesting depth\n";
        $documentation .= "Note: Field selection works at every nesting level independently.\n\n";

        $documentation .= "Examples:\n";
        $documentation .= "- include={$relationNames} (include all fields)\n";

        if ($firstRelationName && $firstRepositoryClass) {
            $firstFields = static::getRelationshipFields($firstRepositoryClass, $request);
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
