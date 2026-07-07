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
class GetOperationDetailsTool extends Tool
{
    use WrapperToolHelpers;

    public function name(): string
    {
        return 'get-operation-details';
    }

    public function description(): string
    {
        return 'Get detailed schema and documentation for a specific repository operation. Returns the complete JSON schema defining all parameters, validation rules, field types, and usage examples. Use this before executing an operation to understand what parameters are required and how to use them.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repository' => $schema->string()
                ->description('The repository URI key (e.g., "users", "posts")')
                ->required(),

            'operation_type' => $schema->string()
                ->description('The type of operation: "index" (list records), "show" (get single record), "store" (create), "update" (modify), "delete" (remove), "profile" (get authenticated user), "action" (custom action), or "getter" (custom getter)')
                ->required(),

            'operation_name' => $schema->string()
                ->description('Required only for "action" and "getter" operation types. The URI key of the specific action or getter to get details for.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()
                ->description('Whether the lookup succeeded.'),
            'operation' => $schema->string()
                ->description('The resolved tool name for the operation.'),
            'type' => $schema->string()
                ->description('The operation type (index, show, store, update, delete, profile, action, getter).'),
            'title' => $schema->string()
                ->description('Human-readable operation title.'),
            'description' => $schema->string()
                ->description('Operation description.'),
            'annotations' => $schema->object()
                ->description('Safety hints for the operation.'),
            'schema' => $schema->object()
                ->description('JSON schema describing the operation parameters.'),
            'examples' => $schema->array()
                ->description('Example parameter payloads for the operation.'),
            'next_steps' => $schema->array()
                ->description('Suggested follow-up tool calls.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $repositoryKey = $request->get('repository');
            $operationType = $request->get('operation_type');
            $operationName = $request->get('operation_name');

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

            $details = McpTools::getOperationDetails($repositoryKey, $operationType, $operationName);

            // Format schema for better readability
            $formattedSchema = $this->formatSchemaForDisplay($details['schema']);

            // Generate examples
            $examples = $this->generateExamplesFromSchema($formattedSchema, $operationType);

            return Response::structured([
                'success' => true,
                'operation' => $details['operation'],
                'type' => $details['type'],
                'title' => $details['title'],
                'description' => $details['description'],
                'annotations' => $details['annotations'],
                'schema' => $formattedSchema,
                'examples' => $examples,
                'next_steps' => [
                    'To execute this operation, use the "execute-operation" tool with the same repository and operation_type',
                    'Provide the required parameters according to the schema above',
                    'Example: execute-operation with repository="'.$repositoryKey.'", operation_type="'.$operationType.'"'.($operationName ? ', operation_name="'.$operationName.'"' : ''),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 'INVALID_OPERATION');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while retrieving operation details',
                'OPERATION_DETAILS_ERROR',
                detail: config('app.debug') ? $e->getMessage() : null,
            );
        }
    }
}
