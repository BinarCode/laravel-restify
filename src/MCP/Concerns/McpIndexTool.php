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

        $searchableFields = (new SearchablesCollection(static::searchables()))->fieldNames();

        if (! empty($searchableFields)) {
            $properties['search'] = $schema->string()
                ->description('Full-text search across: '.implode(', ', $searchableFields).' (search=term).');
        }

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
            ->filter(fn ($option) => is_string($option) && $option !== '')
            ->values()
            ->toArray();

        if (! empty($sortOptions)) {
            $properties['sort'] = $schema->string()
                ->description("Sort $key by one of: ".implode(', ', $sortOptions).". Prefix with '-' for descending (sort=field or sort=-field).");
        }

        MatchesCollection::make(static::matches())
            ->normalize()
            ->authorized(app(McpRequest::class))
            ->each(function (MatchFilter $matchFilter) use ($schema, &$properties) {
                $filterKey = $matchFilter->column();
                $description = $matchFilter->description();

                $properties[$filterKey] = match ($matchFilter->getType()) {
                    RestifySearchable::MATCH_INTEGER, 'integer' => $schema->integer()
                        ->description($description),
                    RestifySearchable::MATCH_BOOL, 'boolean' => $schema->boolean()
                        ->description($description),
                    default => $schema->string()
                        ->description($description)
                };
            });

        return $properties;
    }
}
