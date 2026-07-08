<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class DiscoverRepositoriesTool extends Tool
{
    use WrapperToolHelpers;

    public function name(): string
    {
        return 'discover-repositories';
    }

    public function description(): string
    {
        return 'Discover available Restify repositories and their operations. Returns a list of all repositories with their available CRUD operations, actions, and getters. Use this as the first step to explore what data and operations are available in the application.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Optional search term to filter repositories by name, label, or description. Case-insensitive partial matching.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()
                ->description('Whether the discovery succeeded.'),
            'total' => $schema->integer()
                ->description('Number of repositories returned.'),
            'repositories' => $schema->array()
                ->description('The MCP-enabled repositories with their operation metadata.'),
            'next_steps' => $schema->array()
                ->description('Suggested follow-up tool calls.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $search = $request->get('search');

            $repositories = McpTools::getAvailableRepositories($search);

            return Response::structured([
                'success' => true,
                'total' => $repositories->count(),
                'repositories' => $repositories->toArray(),
                'next_steps' => [
                    'To see detailed operations for a repository, use the "get-repository-operations" tool with the repository name',
                    'Example: get-repository-operations with repository="users"',
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while discovering repositories',
                'DISCOVERY_ERROR',
                detail: config('app.debug') ? $e->getMessage() : null,
            );
        }
    }
}
