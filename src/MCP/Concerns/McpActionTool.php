<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Repository
 */
trait McpActionTool
{
    public function actionTool(Action $action, McpActionRequest $actionRequest): array
    {
        if ($id = $actionRequest->input('id')) {
            if (! $action->authorizedToRun($actionRequest, $actionRequest->findModelOrFail($id, static::uriKey()))) {
                return [
                    'error' => 'Not authorized to run this action',
                    'action' => $action->uriKey(),
                ];
            }
        }

        if (! $action->authorizedToSee($actionRequest)) {
            return [
                'error' => 'Not authorized to see this action',
                'action' => $action->uriKey(),
            ];
        }

        $result = $action->handleRequest($actionRequest);

        return [
            'success' => true,
            'action' => $action->uriKey(),
            'result' => $result->getData(),
        ];
    }

    public static function actionToolSchema(Action $action, JsonSchema $schema, McpActionRequest $mcpRequest): array
    {
        $modelName = class_basename(static::guessModelClassName());
        $properties = [];

        // Add action-specific validation rules
        $actionRules = $action->rules();
        foreach ($actionRules as $field => $rules) {
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

            $fieldSchema->description("Action parameter: {$field}");
            $properties[$field] = $fieldSchema;
        }

        // Add context-specific fields based on action type
        if ($action->isStandalone()) {
            // Standalone actions don't need ID or repositories
            $properties['include'] = $schema->string()
                ->description('Comma-separated list of relationships to include in response');
        } else {
            // Check if it's primarily a show action or index action
            $shownOnShow = $action->isShownOnShow($mcpRequest, app(static::class));
            $shownOnIndex = $action->isShownOnIndex($mcpRequest, app(static::class));

            if ($shownOnShow && ! $shownOnIndex) {
                // Show action - requires single ID
                $properties['id'] = $schema->string()
                    ->description("The ID of the {$modelName} to perform the action on")
                    ->required();

                $properties['include'] = $schema->string()
                    ->description('Comma-separated list of relationships to include');
            } else {
                // Index action - requires repositories array
                $properties['repositories'] = $schema->string()
                    ->description("Array of {$modelName} IDs to perform the action on. e.g. repositories=[1,2,3]")
                    ->required();

                $properties['include'] = $schema->string()
                    ->description('Comma-separated list of relationships to include');
            }
        }

        return $properties;
    }
}
