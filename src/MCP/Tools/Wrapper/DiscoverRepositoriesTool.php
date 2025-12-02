<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

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

    public function handle(Request $request): Response
    {
        try {
            $search = $request->get('search');

            $repositories = McpTools::getAvailableRepositories($search);

            return Response::json([
                'success' => true,
                'total' => $repositories->count(),
                'repositories' => $repositories->toArray(),
                'next_steps' => [
                    'To see detailed operations for a repository, use the "get-repository-operations" tool with the repository name',
                    'Example: get-repository-operations with repository="users"',
                ],
            ]);
        } catch (\Exception $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'DISCOVERY_ERROR'));
        }
    }
}
