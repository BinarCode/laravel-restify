<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Wrapper;

use Binaryk\LaravelRestify\MCP\Concerns\WrapperToolHelpers;
use Binaryk\LaravelRestify\MCP\McpTools;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

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

    public function handle(Request $request): Response
    {
        try {
            $repositoryKey = $request->get('repository');
            $operationType = $request->get('operation_type');
            $operationName = $request->get('operation_name');

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

            $details = McpTools::getOperationDetails($repositoryKey, $operationType, $operationName);

            // Format schema for better readability
            $formattedSchema = $this->formatSchemaForDisplay($details['schema']);

            // Generate examples
            $examples = $this->generateExamplesFromSchema($formattedSchema, $operationType);

            return Response::json([
                'success' => true,
                'operation' => $details['operation'],
                'type' => $details['type'],
                'title' => $details['title'],
                'description' => $details['description'],
                'schema' => $formattedSchema,
                'examples' => $examples,
                'next_steps' => [
                    'To execute this operation, use the "execute-operation" tool with the same repository and operation_type',
                    'Provide the required parameters according to the schema above',
                    'Example: execute-operation with repository="'.$repositoryKey.'", operation_type="'.$operationType.'"'.($operationName ? ', operation_name="'.$operationName.'"' : ''),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'INVALID_OPERATION'));
        } catch (\Exception $e) {
            return Response::json($this->buildErrorResponse($e->getMessage(), 'OPERATION_DETAILS_ERROR'));
        }
    }
}
