<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchesCollection;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpIndexTool
{
    public function indexTool(array $arguments, McpIndexRequest $request): array
    {
        $request->merge($arguments);
        $this->sanitizeToolRequest($request, $arguments);

        return $this->indexAsArray($request);
    }

    public static function indexToolSchema(ToolInputSchema $schema): void
    {
        $key = static::uriKey();

        $schema->number('page')
            ->description('Page number for pagination');

        $schema->number('perPage')
            ->description("Number of $key per page");

        $schema->string('include')
            ->description(static::formatRelationshipDocumentation(app(McpIndexRequest::class)));

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

        MatchesCollection::make(static::matches())
            ->normalize()
            ->authorized(app(McpRequest::class))
            ->each(function (MatchFilter $matchFilter) use ($schema, $key) {
                $filterKey = $matchFilter->column();

                return match ($matchFilter->getType()) {
                    RestifySearchable::MATCH_INTEGER, 'integer' => $schema->integer($filterKey)
                        ->description("Filter $key resource. Description: ".$matchFilter->description()),
                    RestifySearchable::MATCH_BOOL, 'boolean' => $schema->boolean($filterKey)
                        ->description("Filter $key resource. Description: ".$matchFilter->description()),
                    default => $schema->string($filterKey)
                        ->description("Filter $key resource. Description: ".$matchFilter->description())
                };
            });
    }
}
