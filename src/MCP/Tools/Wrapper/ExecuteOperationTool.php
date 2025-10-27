<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ExecuteOperationTool extends Tool
{
    use WrapperToolHelpers;

    public function name(): string
    {
        return 'execute-operation';
    }

    public function description(): string
    {
        return 'Execute a repository operation with the provided parameters. This is the final step after discovering repositories, listing operations, and getting operation details. Pass the required parameters according to the operation schema to perform CRUD operations, execute actions, or call getters.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repository' => $schema->string()
                ->description('The repository URI key (e.g., "users", "posts")')
                ->required(),

            'operation_type' => $schema->string()
                ->description('The type of operation to execute: "index", "show", "store", "update", "delete", "profile", "action", or "getter"')
                ->required(),

            'operation_name' => $schema->string()
                ->description('Required only for "action" and "getter" operation types. The URI key of the specific action or getter to execute.'),

            'parameters' => $schema->object()
                ->description('The parameters to pass to the operation. The required parameters depend on the operation type. Use get-operation-details to see the schema for the specific operation.'),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $repositoryKey = $request->get('repository');
            $operationType = $request->get('operation_type');
            $operationName = $request->get('operation_name');
            $parameters = $request->get('parameters', []);

            if (! $repositoryKey) {
                return Response::json($this->buildErrorResponse(
                    'Repository parameter is required',
                    'MISSING_PARAMETER'
                ));
            }

            if (! $operationType) {
                return Response::json($this->buildErrorResponse(
                    'Operation type parameter is required',
                    'MISSING_PARAMETER'
                ));
            }

            if (in_array($operationType, ['action', 'getter']) && ! $operationName) {
                return Response::json($this->buildErrorResponse(
                    "Operation name is required for {$operationType} operation type",
                    'MISSING_PARAMETER'
                ));
            }

            if (! is_array($parameters)) {
                return Response::json($this->buildErrorResponse(
                    'Parameters must be an object/array',
                    'INVALID_PARAMETERS'
                ));
            }

            // Execute the operation through the registry
            $result = McpTools::executeOperation($repositoryKey, $operationType, $operationName, $parameters);

            return $result;
        } catch (\InvalidArgumentException $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'INVALID_OPERATION'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::json([
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::json($this->buildErrorResponse(
                'Not authorized to perform this operation',
                'AUTHORIZATION_ERROR'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::json($this->buildErrorResponse(
                'Record not found',
                'NOT_FOUND'
            ));
        } catch (\Exception $e) {
            return Response::json([
                'error' => $e->getMessage(),
                'code' => 'EXECUTION_ERROR',
                'type' => get_class($e),
            ]);
        }
    }
}
