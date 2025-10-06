<?php

namespace Binaryk\LaravelRestify\Actions\Concerns;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Illuminate\JsonSchema\JsonSchema;

/**
 * @mixin Action
 */
trait HasSchemaResolver
{
    protected function resolveActionSchema(JsonSchema $schema): array
    {
        $fields = [];

        $allRules = $this->rules();

        foreach ($allRules as $field => $rules) {
            if (str_contains($field, '.*') || str_contains($field, '.*.')) {
                continue;
            }

            // Check if this field has nested rules (e.g., employee.* exists)
            $fieldType = $this->guessTypeFromValidationRules($rules, $field, $allRules);

            $schemaField = match ($fieldType) {
                'boolean' => $schema->boolean(),
                'number' => $schema->number(),
                'array' => $schema->array(),
                default => $schema->string()
            };

            if ($this->isRequired($rules)) {
                $schemaField->required();
            }

            $fields[$field] = $schemaField;
        }

        if ($this->isStandalone()) {
            return $fields;
        }

        if ($this->isShownOnIndex(app(McpActionRequest::class), $this->repository)) {
            $fields['repositories'] = $schema->array()
                ->items(
                    $schema->string()
                )
                ->required()
                ->description("Array of {$modelName} IDs to run the {$actionName} action on.");
        } else {
            $fields['id'] = $schema->string()
                ->description("The ID of the {$modelName} to run the {$actionName} action on.")
                ->required();
        }
    }
}
