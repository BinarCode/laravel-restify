<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRepositoryOperationsTool extends Tool
{
    use WrapperToolHelpers;

    public function name(): string
    {
        return 'get-repository-operations';
    }

    public function description(): string
    {
        return 'Get detailed list of operations available for a specific repository. Returns all CRUD operations (index, show, store, update, delete), custom actions, and getters that are available for the specified repository. Use this after discovering repositories to see what operations you can perform.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repository' => $schema->string()
                ->description('The repository URI key (e.g., "users", "posts"). Use discover-repositories to see available repositories.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $repositoryKey = $request->get('repository');

            if (! $repositoryKey) {
                return Response::json($this->buildErrorResponse(
                    'Repository parameter is required',
                    'MISSING_PARAMETER'
                ));
            }

            $operations = McpTools::getRepositoryOperations($repositoryKey);

            $nextSteps = [];

            if (! empty($operations['operations'])) {
                $nextSteps[] = 'To see detailed schema for a CRUD operation, use "get-operation-details" tool';
                $nextSteps[] = 'Example: get-operation-details with repository="'.$repositoryKey.'", operation_type="index"';
            }

            if (! empty($operations['actions'])) {
                $nextSteps[] = 'For action details, use "get-operation-details" with operation_type="action" and operation_name';
                $nextSteps[] = 'Example: get-operation-details with repository="'.$repositoryKey.'", operation_type="action", operation_name="'.$operations['actions'][0]['name'].'"';
            }

            if (! empty($operations['getters'])) {
                $nextSteps[] = 'For getter details, use "get-operation-details" with operation_type="getter" and operation_name';
            }

            return Response::json([
                'success' => true,
                'repository' => $operations['repository'],
                'label' => $operations['label'],
                'description' => $operations['description'],
                'operations' => $operations['operations'],
                'actions' => $operations['actions'],
                'getters' => $operations['getters'],
                'summary' => [
                    'crud_operations_count' => count($operations['operations']),
                    'actions_count' => count($operations['actions']),
                    'getters_count' => count($operations['getters']),
                ],
                'next_steps' => $nextSteps,
            ]);
        } catch (\InvalidArgumentException $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'INVALID_REPOSITORY'));
        } catch (\Exception $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'OPERATION_LISTING_ERROR'));
        }
    }
}
