<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\Type;

/**
 * @mixin \Binaryk\LaravelRestify\Fields\Field
 */
trait FieldMcpSchemaDetection
{
    /**
     * Guess the field type based on validation rules, field class, and attribute patterns.
     */
    public function guessFieldType(RestifyRequest $request): Type
    {
        $schema = new JsonSchemaTypeFactory();

        $rules = $this->getRulesForRequest($request);

        $ruleType = app(JsonSchemaFromRulesAction::class)->buildTypeFromRules(
            $schema,
            $this->attribute,
            $rules,
        );

        if ($ruleType) {
            return $ruleType;
        }

        // Check attribute name patterns
        $attributeType = $this->guessTypeFromAttributeName($schema);

        if ($attributeType) {
            return $attributeType;
        }

        return $schema->string();
    }

    public function getDescription(RestifyRequest $request, Repository $repository): string
    {
        ray('getting description for '.$this->attribute);
        if (is_callable($this->descriptionCallback)) {
            $description = call_user_func($this->descriptionCallback, $this, $repository);

            if (is_string($description)) {
                return $description;
            }
        }

        if ($description = data_get($this->jsonSchema()?->toArray(), 'description')) {
            if (is_string($description)) {
                return $description;
            }
        }

        $attribute = $this->label ?? $this->attribute;

        $description = "Field: {$attribute}.";

        // Add validation rules information
        $rules = $this->getRulesForRequest($request);

        if (! empty($rules)) {
            $ruleDescriptions = $this->formatValidationRules($rules);

            if (! empty($ruleDescriptions)) {
                $description .= '. Validation: '.implode(', ', $ruleDescriptions);
            }
        }

        // Add file information for file fields
        if ($this instanceof File) {
            $description .= '. Upload a file';
        }

        // Add examples based on field type and name
        if ($this->jsonSchema instanceof Type) {
            $examples = $this->generateFieldExamples($this->jsonSchema);

            if (! empty($examples)) {
                $description .= '. Examples: '.implode(', ', $examples);
            }
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
    protected function generateFieldExamples(JsonSchema $fieldType): array
    {
        $attribute = strtolower($this->attribute);

        if ($fieldType instanceof BooleanType) {
            return ['true', 'false'];
        }

        if ($fieldType instanceof NumberType) {
            return $this->getNumberExamples($attribute);
        }

        if ($fieldType instanceof ArrayType) {
            return ['["item1", "item2"]', '{"key": "value"}'];
        }

        return $this->getStringExamples($attribute);
    }

    /**
     * Get number field examples.
     */
    protected function getNumberExamples(string $attribute): array
    {
        if (str_contains($attribute, 'price') || str_contains($attribute, 'cost') || str_contains($attribute,
                'amount')) {
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

    /**
     * Guess type from validation rules.
     */
    protected function guessTypeFromValidationRules(): ?string
    {
        $allRules = array_merge($this->rules, $this->storingRules, $this->updatingRules);

        // Convert rule objects to strings for checking
        $ruleStrings = collect($allRules)->map(function ($rule) {
            if (is_string($rule)) {
                return $rule;
            }
            if (is_object($rule)) {
                return get_class($rule);
            }

            return (string) $rule;
        })->toArray();

        // Check for specific types
        if ($this->hasAnyRule($ruleStrings, ['boolean', 'bool'])) {
            return 'boolean';
        }

        if ($this->hasAnyRule($ruleStrings, ['array'])) {
            return 'array';
        }

        if ($this->hasAnyRule($ruleStrings, ['email', 'url', 'ip', 'uuid', 'string', 'regex'])) {
            return 'string';
        }

        if ($this->hasAnyRule($ruleStrings,
            ['date', 'date_format:', 'before:', 'after:', 'before_or_equal:', 'after_or_equal:'])) {
            return 'string'; // Dates are typically handled as strings in schemas
        }

        if ($this->hasAnyRule($ruleStrings, ['file', 'image', 'mimes:', 'mimetypes:'])) {
            return 'string'; // Files are typically handled as strings (paths/URLs)
        }

        if ($this->hasAnyRule($ruleStrings, ['integer', 'int', 'numeric', 'between:'])) {
            return 'number';
        }

        return null;
    }

    /**
     * Check if any of the given rules exist in the rule strings.
     */
    protected function hasAnyRule(array $ruleStrings, array $rulesToCheck): bool
    {
        foreach ($ruleStrings as $rule) {
            foreach ($rulesToCheck as $check) {
                if ($rule === $check || str_starts_with($rule, $check)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Guess type from attribute name patterns.
     */
    protected function guessTypeFromAttributeName(JsonSchema $schema): ?Type
    {
        $attribute = $this->attribute;

        if (! is_string($attribute)) {
            return null;
        }

        $attribute = strtolower($attribute);

        // Boolean patterns
        if (preg_match('/^(is_|has_|can_|should_|will_|was_|were_)/', $attribute) ||
            in_array($attribute,
                ['active', 'enabled', 'disabled', 'verified', 'published', 'featured', 'public', 'private'])) {
            return $schema->boolean();
        }

        // Number patterns
        if (preg_match('/_(id|count|number|amount|price|cost|total|sum|quantity|qty)$/', $attribute) ||
            in_array($attribute,
                ['id', 'age', 'year', 'month', 'day', 'hour', 'minute', 'second', 'weight', 'height', 'size'])) {
            return $schema->number();
        }

        // Date patterns
        if (preg_match('/_(at|date|time)$/', $attribute) ||
            in_array($attribute,
                ['created_at', 'updated_at', 'deleted_at', 'published_at', 'birthday', 'date_of_birth'])) {
            return $schema->string()->description('The attribute should be a date string in ISO 8601 format (e.g., "2024-01-01T00:00:00Z")');
        }

        // Email pattern
        if (str_contains($attribute, 'email')) {
            return $schema->string();
        }

        // Password pattern
        if (str_contains($attribute, 'password')) {
            return $schema->string();
        }

        // Array patterns (JSON fields)
        if (preg_match('/_(json|data|metadata|config|settings|options)$/', $attribute) ||
            str_contains($attribute, 'tags')) {
            return $schema->array()->items(
                $schema->string()
            );
        }

        return null;
    }
}
