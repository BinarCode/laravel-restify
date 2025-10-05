<?php

namespace Binaryk\LaravelRestify\MCP\Actions;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationRuleParser;

class JsonSchemaFromRulesAction
{
    use SchemaAttributes;

    protected array $rulesSchema = [];

    protected array $requiredAttributes = [];

    /**
     * Convert Laravel validation rules to JSON Schema types.
     *
     * @param  JsonSchema  $schema  The JSON Schema factory instance
     * @param  array<string, array<int, string|\Illuminate\Contracts\Validation\Rule>>  $allRules  Associative array where keys are attribute names and values are arrays of validation rules
     * @return array<string, Type> Array of JSON Schema types keyed by attribute name
     */
    public function __invoke(JsonSchema $schema, array $allRules): array
    {
        $formatedRules = validator()
            ->make([], $allRules)
            ->getRules();

        foreach ($formatedRules as $attribute => $rules) {
            $this->buildTypeFromRules($schema, $attribute, $rules);
        }

        $this->processWildcardRules($schema, $allRules);

        return $this->rulesSchema;
    }

    public function buildTypeFromRules(JsonSchema $schema, string $attribute, array $rules): ?Type
    {
        foreach ($rules as $rule) {
            $type = $this->buildTypeFromRule($schema, $attribute, $rule);

            if ($this->isAttributeRequired($attribute)) {
                $type?->required();
            }

            if ($type) {
                $this->rulesSchema[$attribute] = $type;
            }
        }

        return data_get($this->rulesSchema, $attribute);
    }

    public function buildTypeFromRule(JsonSchema $schema, string $attribute, $rule): ?Type
    {
        $existingType = $this->rulesSchema[$attribute] ?? null;

        if ($rule instanceof Rule || $rule instanceof ValidationRule) {
            $class = get_class($rule);

            if ($existingType) {
                return $existingType;
            }

            $schemaType = match (true) {
                $rule instanceof Email => $schema->string()
                    ->description("This field must be a valid email address."),
                $rule instanceof File => $schema->string()
                    ->description("This field must be a valid file path."),
                $rule instanceof Password => $schema->string()
                    ->description("This field must be a valid password."),
                default => $schema->string()
                    ->description("This field uses a custom validation rule: {$class}."),
            };

            return $schemaType;
        }

        if ($rule instanceof Closure) {
            return $existingType ?? $schema->string()
                ->description("This field uses a custom validation closure.");
        }

        [$rule, $parameters] = ValidationRuleParser::parse($rule);

        if ($rule === '') {
            return null;
        }

        $method = 'validate'.$rule;

        if (! method_exists($this, $method)) {
            return null;
        }

        return $this->$method($attribute, $schema, $parameters);
    }

    protected function processWildcardRules(JsonSchema $schema, array $allRules): void
    {
        foreach ($allRules as $attribute => $rules) {
            if (! str_ends_with($attribute, '.*')) {
                continue;
            }

            $parentField = substr($attribute, 0, -2);

            $itemType = null;

            foreach ($rules as $rule) {
                // Use a unique attribute name to prevent circular references
                // when the same type (e.g., 'array') is used for both parent and items
                $uniqueAttribute = '_item_'.$parentField;
                $type = $this->buildTypeFromRule($schema, $uniqueAttribute, $rule);

                if ($type) {
                    $itemType = $type;
                }
            }

            if ($itemType && isset($this->rulesSchema[$parentField]) && $this->rulesSchema[$parentField] instanceof ArrayType) {
                $this->rulesSchema[$parentField]->items($itemType);
            }
        }
    }

    public static function getPrimitiveTypeFromSchemaType(JsonSchema $schema): string
    {
        return match (get_class($schema)) {
            ArrayType::class => 'array',
            BooleanType::class => 'boolean',
            IntegerType::class => 'integer',
            NumberType::class => 'number',
            ObjectType::class => 'object',
            default => 'string',
        };
    }

    protected function isAttributeRequired(string $attribute): bool
    {
        return in_array($attribute, $this->requiredAttributes, true);
    }

    protected function markAttributeAsRequired(string $attribute): void
    {
        if (! in_array($attribute, $this->requiredAttributes, true)) {
            $this->requiredAttributes[] = $attribute;
        }
    }
}
