<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpActionTool
{
    public function actionTool(Action $action, array $arguments, McpActionRequest $actionRequest): array
    {
        $actionRequest->merge($arguments);

        $this->sanitizeToolRequest($actionRequest, $arguments);

        if ($id = $actionRequest->input('id')) {
            if (! $action->authorizedToRun($actionRequest, $actionRequest->findModelOrFail($id))) {
                return [
                    'error' => 'Not authorized to run this action',
                    'getter' => $action->uriKey(),
                ];
            }
        }

        // Set up the action request context based on action type
        if (! $action->isStandalone()) {
            if (isset($arguments['id'])) {
                // Single model action (show context)
                $actionRequest->merge(['id' => $arguments['id']]);
            } elseif (isset($arguments['repositories'])) {
                // Multiple models action (index context)
                $actionRequest->merge(['repositories' => $arguments['repositories']]);
            }
        }

        // Check authorization
        if (! $action->authorizedToSee($actionRequest)) {
            return [
                'error' => 'Not authorized to see this action',
                'action' => $action->uriKey(),
            ];
        }

        try {
            $result = $action->handleRequest($actionRequest);

            return [
                'success' => true,
                'action' => $action->uriKey(),
                'result' => $result->getData(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'action' => $action->uriKey(),
            ];
        }
    }

    public static function actionToolSchema(Action $action, ToolInputSchema $schema, McpActionRequest $mcpRequest): void
    {
        $modelName = class_basename(static::guessModelClassName());

        // Add action-specific validation rules
        $actionRules = $action->rules();
        foreach ($actionRules as $field => $rules) {
            $rulesArray = is_array($rules) ? $rules : explode('|', $rules);
            $isRequired = in_array('required', $rulesArray);

            // Determine field type based on rules
            if (in_array('boolean', $rulesArray)) {
                $fieldSchema = $schema->boolean($field);
            } elseif (in_array('integer', $rulesArray) || in_array('numeric', $rulesArray)) {
                $fieldSchema = $schema->number($field);
            } elseif (in_array('array', $rulesArray)) {
                $fieldSchema = $schema->string($field);
            } else {
                $fieldSchema = $schema->string($field);
            }

            if ($isRequired) {
                $fieldSchema->required();
            }

            $fieldSchema->description("Action parameter: {$field}");
        }

        // Add context-specific fields based on action type
        if ($action->isStandalone()) {
            // Standalone actions don't need ID or repositories
            $schema->string('include')
                ->description('Comma-separated list of relationships to include in response');
        } else {
            // Check if it's primarily a show action or index action
            $shownOnShow = $action->isShownOnShow($mcpRequest, app(static::class));
            $shownOnIndex = $action->isShownOnIndex($mcpRequest, app(static::class));

            if ($shownOnShow && ! $shownOnIndex) {
                // Show action - requires single ID
                $schema->string('id')
                    ->description("The ID of the {$modelName} to perform the action on")
                    ->required();

                $schema->string('include')
                    ->description('Comma-separated list of relationships to include');
            } else {
                // Index action - requires repositories array
                $schema->string('repositories')
                    ->description("Array of {$modelName} IDs to perform the action on. e.g. repositories=[1,2,3]")
                    ->required();

                $schema->string('include')
                    ->description('Comma-separated list of relationships to include');
            }
        }
    }
}
