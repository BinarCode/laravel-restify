<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchesCollection;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Repository
 */
trait McpIndexTool
{
    public function indexTool(McpIndexRequest $request): array
    {
        return $this->index($request)->getData(true);
    }

    public static function indexToolSchema(JsonSchema $schema): array
    {
        $key = static::uriKey();

        $properties = [
            'page' => $schema->number()
                ->description('Page number for pagination'),

            'perPage' => $schema->number()
                ->description("Number of $key per page"),

            'include' => $schema->string()
                ->description(static::formatRelationshipDocumentation(app(McpIndexRequest::class))),
        ];

        $searchableFields = (new SearchablesCollection(static::searchables()))->formatForDocumentation();

        $properties['search'] = $schema->string()
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

        $properties['sort'] = $schema->string()
            ->description("Sorting criteria for the $key. Available options: ".implode(', ',
                $sortOptions).' (e.g., sort=field or sort=-field for descending)');

        MatchesCollection::make(static::matches())
            ->normalize()
            ->authorized(app(McpRequest::class))
            ->each(function (MatchFilter $matchFilter) use ($schema, $key, &$properties) {
                $filterKey = $matchFilter->column();

                $properties[$filterKey] = match ($matchFilter->getType()) {
                    RestifySearchable::MATCH_INTEGER, 'integer' => $schema->integer()
                        ->description("Filter $key resource. Description: ".$matchFilter->description()),
                    RestifySearchable::MATCH_BOOL, 'boolean' => $schema->boolean()
                        ->description("Filter $key resource. Description: ".$matchFilter->description()),
                    default => $schema->string()
                        ->description("Filter $key resource. Description: ".$matchFilter->description())
                };
            });

        return $properties;
    }
}
