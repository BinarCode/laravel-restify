<?php

namespace Binaryk\LaravelRestify\MCP\Tools;

use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\GlobalSearch;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class GlobalSearchTool extends Tool
{
    public function name(): string
    {
        return 'global-search';
    }

    public function description(): string
    {
        return 'Search across all repositories in the Laravel Restify application. Returns matching records from all searchable repositories with repository context, titles, and direct API links.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $searchableRepositories = collect(Restify::globallySearchableRepositories(app(McpRequest::class)));

        // Build searchable fields documentation across all repositories
        $searchableInfo = $searchableRepositories->map(function ($repo) {
            $searchableFields = (new SearchablesCollection($repo::searchables()))->formatForDocumentation();

            return "{$repo::uriKey()} ({$searchableFields})";
        })->implode(', ');

        $schema->string('search')
            ->description("Search query to find records across all repositories. Searchable fields by repository: {$searchableInfo}")
            ->required();

        $schema->integer('limit')
            ->description('Maximum number of results to return (default: uses each repository\'s globalSearchResults setting)');

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $request = app(McpRequest::class);
        $request->merge([
            'search' => $arguments['search'] ?? '',
        ]);

        // If limit is provided, we could apply it per repository, but for now
        // we'll respect each repository's globalSearchResults setting
        // This matches the behavior of GlobalSearchController

        $globallySearchableRepositories = Restify::globallySearchableRepositories($request);

        $results = (new GlobalSearch(
            $request,
            $globallySearchableRepositories
        ))->get();

        return ToolResult::json([
            'results' => $results,
            'total' => count($results),
            'searched_repositories' => collect($globallySearchableRepositories)->map(fn ($repo) => [
                'name' => $repo::uriKey(),
                'title' => $repo::label(),
            ])->values()->toArray(),
        ]);
    }
}
