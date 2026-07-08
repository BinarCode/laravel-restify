<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Repository
 */
trait McpGetterTool
{
    public function getterTool(Getter $getter, McpGetterRequest $getterRequest): array
    {
        if ($id = $getterRequest->input('id')) {
            if (! $getter->authorizedToRun($getterRequest, $getterRequest->findModelOrFail($id, static::uriKey()))) {
                return [
                    'error' => 'Not authorized to run this getter',
                    'getter' => $getter->uriKey(),
                ];
            }
        }

        $result = $getter->handleRequest($getterRequest);

        $responseData = $result instanceof JsonResponse
            ? $result->getData()
            : $result->getContent();

        return [
            'success' => true,
            'getter' => $getter->uriKey(),
            'result' => $responseData,
        ];
    }

    public static function getterToolSchema(Getter $getter, JsonSchema $schema, McpGetterRequest $mcpRequest): array
    {
        $modelName = class_basename(static::guessModelClassName());
        $properties = [];

        // Add getter-specific validation rules if the getter has a rules method
        if (method_exists($getter, 'rules')) {
            $getterRules = $getter->rules();
            foreach ($getterRules as $field => $rules) {
                $rulesArray = is_array($rules) ? $rules : explode('|', $rules);
                $isRequired = in_array('required', $rulesArray);

                // Determine field type based on rules
                if (in_array('boolean', $rulesArray)) {
                    $fieldSchema = $schema->boolean();
                } elseif (in_array('integer', $rulesArray) || in_array('numeric', $rulesArray)) {
                    $fieldSchema = $schema->number();
                } elseif (in_array('array', $rulesArray)) {
                    $fieldSchema = $schema->string();
                } else {
                    $fieldSchema = $schema->string();
                }

                if ($isRequired) {
                    $fieldSchema->required();
                }

                $fieldSchema->description("Getter parameter: {$field}");
                $properties[$field] = $fieldSchema;
            }
        }

        // Check if it's primarily a show getter or index getter
        $shownOnShow = $getter->isShownOnShow($mcpRequest, app(static::class));
        $shownOnIndex = $getter->isShownOnIndex($mcpRequest, app(static::class));

        if ($shownOnShow && ! $shownOnIndex) {
            // Show getter - requires single ID
            $properties['id'] = $schema->string()
                ->description("The ID of the {$modelName} to execute the getter on")
                ->required();

            $properties['include'] = $schema->string()
                ->description('Comma-separated list of relationships to include in response');
        } else {
            // Index getters typically don't require specific IDs as they work on collections/aggregates
            $properties['include'] = $schema->string()
                ->description('Comma-separated list of relationships to include in response');
        }

        return $properties;
    }
}
