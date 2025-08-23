<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Repositories\Repository;
use Laravel\Mcp\Server\Tools\ToolInputSchema;

trait FieldMcpSchemaDetection
{
    /**
     * Resolve the tool schema for this field to be used in MCP tools.
     */
    public function resolveToolSchema(ToolInputSchema $schema, Repository $repository): self
    {
        // Check if there's a custom callback defined
        if (is_callable($this->toolInputSchemaCallback)) {
            call_user_func($this->toolInputSchemaCallback, $schema, $repository, $this);

            return $this;
        }

        // Skip computed fields for default implementation
        if ($this->computed()) {
            return $this;
        }

        $attribute = $this->label ?? $this->attribute;
        $fieldType = $this->guessFieldType();

        // Add the field to schema based on its type
        $schemaField = match ($fieldType) {
            'boolean' => $schema->boolean($attribute),
            'number' => $schema->number($attribute),
            'array' => $schema->string($attribute), // Arrays are typically sent as JSON strings
            default => $schema->string($attribute)
        };

        // Add description
        $description = $this->generateFieldDescription($repository);
        $schemaField->description($description);

        // Mark as required if field has required validation
        if ($this->isRequired()) {
            $schemaField->required();
        }

        return $this;
    }

    /**
     * Generate a comprehensive description for the field.
     */
    protected function generateFieldDescription(Repository $repository): string
    {
        $attribute = $this->label ?? $this->attribute;
        $fieldType = $this->guessFieldType();

        $description = "Field: {$attribute} (type: {$fieldType})";

        // Add validation rules information
        $rules = $this->getStoringRules();
        if (! empty($rules)) {
            $ruleDescriptions = $this->formatValidationRules($rules);
            if (! empty($ruleDescriptions)) {
                $description .= '. Validation: '.implode(', ', $ruleDescriptions);
            }
        }

        // Add relationship information for relationship fields
        if ($this->isRelationshipField()) {
            $description .= '. This is a relationship field';
        }

        // Add file information for file fields
        if ($this instanceof File) {
            $description .= '. Upload a file';
        }

        // Add examples based on field type and name
        $examples = $this->generateFieldExamples();
        if (! empty($examples)) {
            $description .= '. Examples: '.implode(', ', $examples);
        }

        return $description;
    }

    /**
     * Check if field is required based on validation rules.
     */
    protected function isRequired(): bool
    {
        $rules = $this->getStoringRules();

        return in_array('required', $rules) ||
            collect($rules)->contains(function ($rule) {
                return is_string($rule) && str_starts_with($rule, 'required');
            });
    }

    /**
     * Check if field is a relationship field.
     */
    protected function isRelationshipField(): bool
    {
        return $this instanceof \Binaryk\LaravelRestify\Fields\BelongsTo ||
            $this instanceof \Binaryk\LaravelRestify\Fields\HasOne ||
            $this instanceof \Binaryk\LaravelRestify\Fields\HasMany ||
            $this instanceof \Binaryk\LaravelRestify\Fields\BelongsToMany;
    }

    /**
     * Format validation rules for display.
     */
    protected function formatValidationRules(array $rules): array
    {
        $formatted = [];

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $formatted[] = match (true) {
                    $rule === 'required' => 'required',
                    str_starts_with($rule, 'min:') => 'minimum '.substr($rule, 4).' characters',
                    str_starts_with($rule, 'max:') => 'maximum '.substr($rule, 4).' characters',
                    str_starts_with($rule, 'between:') => 'between '.str_replace(',', ' and ', substr($rule, 8)),
                    $rule === 'email' => 'valid email format',
                    $rule === 'url' => 'valid URL format',
                    $rule === 'numeric' => 'numeric value',
                    $rule === 'integer' => 'integer value',
                    $rule === 'boolean' => 'boolean value (true/false)',
                    $rule === 'array' => 'array format',
                    str_starts_with($rule, 'in:') => 'allowed values: '.str_replace(',', ', ', substr($rule, 3)),
                    default => $rule
                };
            }
        }

        return array_filter($formatted);
    }

    /**
     * Generate examples for the field.
     */
    protected function generateFieldExamples(): array
    {
        $attribute = strtolower($this->attribute);
        $fieldType = $this->guessFieldType();

        return match ($fieldType) {
            'boolean' => ['true', 'false'],
            'number' => $this->getNumberExamples($attribute),
            'array' => ['["item1", "item2"]', '{"key": "value"}'],
            default => $this->getStringExamples($attribute)
        };
    }

    /**
     * Get number field examples.
     */
    protected function getNumberExamples(string $attribute): array
    {
        if (str_contains($attribute, 'price') || str_contains($attribute, 'cost') || str_contains($attribute, 'amount')) {
            return ['99.99', '29.95'];
        }
        if (str_contains($attribute, 'age')) {
            return ['25', '30'];
        }
        if (str_contains($attribute, 'year')) {
            return ['2024', '2023'];
        }
        if (str_ends_with($attribute, '_id')) {
            return ['1', '42'];
        }

        return ['1', '100'];
    }

    /**
     * Get string field examples.
     */
    protected function getStringExamples(string $attribute): array
    {
        if (str_contains($attribute, 'email')) {
            return ['user@example.com', 'john.doe@company.org'];
        }
        if (str_contains($attribute, 'name')) {
            return ['John Doe', 'Sample Name'];
        }
        if (str_contains($attribute, 'title')) {
            return ['Sample Title', 'My Title'];
        }
        if (str_contains($attribute, 'description')) {
            return ['A detailed description...', 'Brief summary'];
        }
        if (str_contains($attribute, 'url') || str_contains($attribute, 'link')) {
            return ['https://example.com', 'https://website.org/path'];
        }
        if (str_contains($attribute, 'phone')) {
            return ['+1234567890', '(555) 123-4567'];
        }
        if (str_contains($attribute, 'password')) {
            return ['SecurePassword123!', 'MyPassword456'];
        }

        return ['sample text', 'example value'];
    }
}
