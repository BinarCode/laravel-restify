<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpGetterTool
{
    public function getterTool(Getter $getter, array $arguments, McpGetterRequest $getterRequest): array
    {
        $getterRequest->merge($arguments);

        $this->sanitizeToolRequest($getterRequest, $arguments);

        if ($id = $getterRequest->input('id')) {
            if (! $getter->authorizedToRun($getterRequest, $getterRequest->findModelOrFail($id))) {
                return [
                    'error' => 'Not authorized to run this getter',
                    'getter' => $getter->uriKey(),
                ];
            }
        }

        try {
            $result = $getter->handleRequest($getterRequest);

            // Handle different response types
            $responseData = $result instanceof JsonResponse
                ? $result->getData()
                : $result->getContent();

            return [
                'success' => true,
                'getter' => $getter->uriKey(),
                'result' => $responseData,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'getter' => $getter->uriKey(),
            ];
        }
    }

    public static function getterToolSchema(Getter $getter, ToolInputSchema $schema, McpGetterRequest $mcpRequest): void
    {
        $modelName = class_basename(static::guessModelClassName());

        // Add getter-specific validation rules if the getter has a rules method
        if (method_exists($getter, 'rules')) {
            $getterRules = $getter->rules();
            foreach ($getterRules as $field => $rules) {
                $rulesArray = is_array($rules) ? $rules : explode('|', $rules);
                $isRequired = in_array('required', $rulesArray);

                // Determine field type based on rules
                if (in_array('boolean', $rulesArray)) {
                    $fieldSchema = $schema->boolean($field);
                } elseif (in_array('integer', $rulesArray) || in_array('numeric', $rulesArray)) {
                    $fieldSchema = $schema->number($field);
                } elseif (in_array('array', $rulesArray)) {
                    $fieldSchema = $schema->array($field);
                } else {
                    $fieldSchema = $schema->string($field);
                }

                if ($isRequired) {
                    $fieldSchema->required();
                }

                $fieldSchema->description("Getter parameter: {$field}");
            }
        }

        // Check if it's primarily a show getter or index getter
        $shownOnShow = $getter->isShownOnShow($mcpRequest, app(static::class));
        $shownOnIndex = $getter->isShownOnIndex($mcpRequest, app(static::class));

        if ($shownOnShow && ! $shownOnIndex) {
            // Show getter - requires single ID
            $schema->string('id')
                ->description("The ID of the {$modelName} to execute the getter on")
                ->required();

            $schema->string('include')
                ->description('Comma-separated list of relationships to include in response');
        } else {
            // Index getters typically don't require specific IDs as they work on collections/aggregates
            $schema->string('include')
                ->description('Comma-separated list of relationships to include in response');
        }
    }
}
