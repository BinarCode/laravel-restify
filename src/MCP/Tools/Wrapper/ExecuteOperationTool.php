<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
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

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $repositoryKey = $request->get('repository');
            $operationType = $request->get('operation_type');
            $operationName = $request->get('operation_name');
            $parameters = $request->get('parameters', []);

            if (! $repositoryKey) {
                return $this->errorResponse(
                    'Repository parameter is required',
                    'MISSING_PARAMETER'
                );
            }

            if (! $operationType) {
                return $this->errorResponse(
                    'Operation type parameter is required',
                    'MISSING_PARAMETER'
                );
            }

            if (in_array($operationType, ['action', 'getter']) && ! $operationName) {
                return $this->errorResponse(
                    "Operation name is required for {$operationType} operation type",
                    'MISSING_PARAMETER'
                );
            }

            if (! is_array($parameters)) {
                return $this->errorResponse(
                    'Parameters must be an object/array',
                    'INVALID_PARAMETERS'
                );
            }

            return McpTools::executeOperation($repositoryKey, $operationType, $operationName, $parameters);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 'INVALID_OPERATION');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 'VALIDATION_ERROR', $e->errors());
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                'Not authorized to perform this operation',
                'AUTHORIZATION_ERROR'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Record not found', 'NOT_FOUND');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while executing the operation',
                'EXECUTION_ERROR',
                detail: config('app.debug') ? $e->getMessage() : null,
            );
        }
    }
}
