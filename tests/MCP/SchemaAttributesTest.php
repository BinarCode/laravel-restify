<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionProperty;

/**
 * The SchemaAttributes trait mirrors Laravel's ValidatesAttributes rule set,
 * but every validateXxx() method returns a JSON Schema Type (with a human
 * readable description) instead of a boolean, so an MCP tool's input schema
 * can describe a repository field's validation rules. JsonSchemaFromRulesAction
 * is the only entry point that dispatches into it, exactly like it is used by
 * Action::jsonSchema(), Getter::jsonSchema() and FieldMcpSchemaDetection.
 */
class SchemaAttributesTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['accepted', null])]
    #[TestWith(['accepted_if:other,value', 'Must be accepted when another attribute has a given value'])]
    #[TestWith(['declined', 'Must be declined (no, off, 0, false)'])]
    #[TestWith(['declined_if:other,value', 'Must be declined when another attribute has a given value'])]
    #[TestWith(['active_url', 'Must be an active URL with valid DNS records'])]
    #[TestWith(['ascii', 'Must be 7 bit ASCII'])]
    #[TestWith(['bail', null])]
    #[TestWith(['list', 'Must be a list (sequential array)'])]
    #[TestWith(['required_array_keys:a,b', 'Array must have all required keys'])]
    #[TestWith(['confirmed', 'Must have a matching confirmation field'])]
    #[TestWith(['decimal:2', 'Must have a specific number of decimal places'])]
    #[TestWith(['different:other', 'Must be different from another attribute'])]
    #[TestWith(['digits:4', 'Must have a specific number of digits'])]
    #[TestWith(['digits_between:1,4', 'Must have digits between a range'])]
    #[TestWith(['dimensions:min_width=100', 'Image must match specified dimensions'])]
    #[TestWith(['distinct', 'Must be unique among other values'])]
    #[TestWith(['extensions:pdf,doc', 'Must be a valid file with allowed extensions'])]
    #[TestWith(['filled', 'Must be filled when present'])]
    #[TestWith(['gt:other', 'Must be greater than another attribute'])]
    #[TestWith(['lt:other', 'Must be less than another attribute'])]
    #[TestWith(['gte:other', 'Must be greater than or equal to another attribute'])]
    #[TestWith(['lte:other', 'Must be less than or equal to another attribute'])]
    #[TestWith(['lowercase', 'Must be lowercase'])]
    #[TestWith(['uppercase', 'Must be uppercase'])]
    #[TestWith(['hex_color', 'Must be a valid HEX color'])]
    #[TestWith(['in_array_keys:a,b', 'Array must have at least one of the specified keys'])]
    #[TestWith(['max_digits:5', 'Must have a maximum number of digits'])]
    #[TestWith(['min_digits:2', 'Must have a minimum number of digits'])]
    #[TestWith(['missing', 'Must be missing from the data'])]
    #[TestWith(['missing_if:other,value', 'Must be missing when another attribute has a given value'])]
    #[TestWith(['missing_unless:other,value', 'Must be missing unless another attribute has a given value'])]
    #[TestWith(['missing_with:other', 'Must be missing when any given attribute is present'])]
    #[TestWith(['missing_with_all:other,another', 'Must be missing when all given attributes are present'])]
    #[TestWith(['multiple_of:2', 'Must be a multiple of a given value'])]
    #[TestWith(['not_in:a,b', 'Must not be one of the specified values'])]
    #[TestWith(['present', 'Must be present in the data'])]
    #[TestWith(['present_if:other,value', 'Must be present when another attribute has a given value'])]
    #[TestWith(['present_unless:other,value', 'Must be present unless another attribute has a given value'])]
    #[TestWith(['present_with:other', 'Must be present when any given attribute is present'])]
    #[TestWith(['present_with_all:other,another', 'Must be present when all given attributes are present'])]
    #[TestWith(['regex:/^[a-z]+$/', 'Must match the specified regular expression'])]
    #[TestWith(['not_regex:/^[a-z]+$/', 'Must not match the specified regular expression'])]
    #[TestWith(['required_if_accepted:other', 'Must be present when another attribute is accepted'])]
    #[TestWith(['required_if_declined:other', 'Must be present when another attribute is declined'])]
    #[TestWith(['prohibited', 'Must not be present or must be empty'])]
    #[TestWith(['prohibited_if:other,value', 'Must not be present when another attribute has a given value'])]
    #[TestWith(['prohibited_if_accepted:other', 'Must not be present when another attribute is accepted'])]
    #[TestWith(['prohibited_if_declined:other', 'Must not be present when another attribute is declined'])]
    #[TestWith(['prohibited_unless:other,value', 'Must not be present unless another attribute has a given value'])]
    #[TestWith(['prohibits:other', 'Prohibits other specified attributes from being present'])]
    #[TestWith(['exclude', 'This attribute is excluded from validation'])]
    #[TestWith(['exclude_if:other,value', 'This attribute is excluded when another attribute has a given value'])]
    #[TestWith(['exclude_unless:other,value', 'This attribute is excluded unless another attribute has a given value'])]
    #[TestWith(['required_unless:other,value', null])]
    #[TestWith(['exclude_with:other', 'This attribute is excluded when another attribute is present'])]
    #[TestWith(['exclude_without:other', 'This attribute is excluded when another attribute is missing'])]
    #[TestWith(['required_with:other', 'Must be present when any other attribute exists'])]
    #[TestWith(['required_with_all:other,another', 'Must be present when all other attributes exist'])]
    #[TestWith(['required_without:other', 'Must be present when another attribute does not exist'])]
    #[TestWith(['required_without_all:other,another', 'Must be present when all other attributes do not exist'])]
    #[TestWith(['sometimes', null])]
    #[TestWith(['required_if:other,value', null])]
    #[TestWith(['exists:users,id', 'Must exist in the database'])]
    #[TestWith(['nullable', 'This field is optional'])]
    public function it_converts_a_rule_with_no_type_detection_into_a_described_string_schema(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['alpha', StringType::class, 'Must contain only alphabetic characters'])]
    #[TestWith(['alpha_dash', StringType::class, 'Must contain only alpha-numeric characters, dashes, and underscores'])]
    #[TestWith(['alpha_num', StringType::class, 'Must contain only alpha-numeric characters'])]
    #[TestWith(['array', ArrayType::class, 'Must be an array'])]
    #[TestWith(['boolean', BooleanType::class, 'Must be a boolean (true/false)'])]
    #[TestWith(['date', StringType::class, 'Must be a valid date format.'])]
    #[TestWith(['email', StringType::class, 'Must be a valid email address.'])]
    #[TestWith(['file', StringType::class, 'Must be a valid file, or file absolute path'])]
    #[TestWith(['image', StringType::class, 'Must be a valid image file'])]
    #[TestWith(['ip', StringType::class, 'Must be a valid IP address'])]
    #[TestWith(['ipv4', StringType::class, 'Must be a valid IPv4 address'])]
    #[TestWith(['ipv6', StringType::class, 'Must be a valid IPv6 address'])]
    #[TestWith(['mac_address', StringType::class, 'Must be a valid MAC address'])]
    #[TestWith(['json', StringType::class, 'Must be valid JSON'])]
    #[TestWith(['timezone', StringType::class, 'Must be a valid timezone'])]
    #[TestWith(['url', StringType::class, 'Must be a valid URL'])]
    #[TestWith(['ulid', StringType::class, 'Must be a valid ULID'])]
    #[TestWith(['uuid', StringType::class, 'Must be a valid UUID'])]
    #[TestWith(['integer', IntegerType::class, 'Must be an integer'])]
    #[TestWith(['numeric', NumberType::class, 'Must be a numeric value'])]
    #[TestWith(['string', StringType::class, 'Must be a string'])]
    public function it_builds_the_default_type_when_none_exists_and_reuses_an_existing_type_of_the_same_kind(string $rule, string $expectedType, string $description): void
    {
        $fresh = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf($expectedType, $fresh['field']);
        $this->assertDescribed($fresh['field'], $description);

        // A second occurrence of the same rule finds the type it already
        // built in $rulesSchema and returns it as-is (the `instanceof`
        // short-circuit), instead of overwriting it with a fresh one.
        $reused = $this->convert(['field' => [$rule, $rule]]);

        $this->assertInstanceOf($expectedType, $reused['field']);
    }

    #[Test]
    public function mimes_reuses_an_existing_string_type_or_describes_the_allowed_extensions(): void
    {
        $default = $this->convert(['field' => ['mimes']]);
        $this->assertDescribed($default['field'], 'Must be a valid file, or file absolute path with allowed extension');

        $withExtensions = $this->convert(['field' => ['mimes:pdf,doc']]);
        $this->assertDescribed($withExtensions['field'], 'Allowed file extensions: pdf, doc');

        $reused = $this->convert(['field' => ['string', 'mimes']]);
        $this->assertInstanceOf(StringType::class, $reused['field']);
    }

    #[Test]
    public function mimetypes_reuses_an_existing_string_type_or_describes_the_allowed_mime_types(): void
    {
        $default = $this->convert(['field' => ['mimetypes']]);
        $this->assertDescribed($default['field'], 'Must be a valid file, or file absolute path with allowed MIME type');

        $withTypes = $this->convert(['field' => ['mimetypes:image/png,image/jpeg']]);
        $this->assertDescribed($withTypes['field'], 'Allowed MIME types: image/png, image/jpeg');

        $reused = $this->convert(['field' => ['string', 'mimetypes']]);
        $this->assertInstanceOf(StringType::class, $reused['field']);
    }

    #[Test]
    #[TestWith(['before:2025-01-01', 'This is a date attribute. Must be before: 2025-01-01'])]
    #[TestWith(['before', null])]
    #[TestWith(['before_or_equal:2025-01-01', 'Must be before or equal to: 2025-01-01'])]
    #[TestWith(['before_or_equal', null])]
    #[TestWith(['after:2025-01-01', 'Date attribute, must be after: 2025-01-01'])]
    #[TestWith(['after', null])]
    #[TestWith(['after_or_equal:2025-01-01', 'Must be after or equal to: 2025-01-01'])]
    #[TestWith(['after_or_equal', null])]
    #[TestWith(['date_equals:2025-01-01', 'Must be equal to date: 2025-01-01'])]
    #[TestWith(['date_equals', null])]
    #[TestWith(['same:other', 'Must match: other'])]
    #[TestWith(['same', null])]
    #[TestWith(['starts_with:foo,bar', 'Must start with: foo, bar'])]
    #[TestWith(['starts_with', null])]
    #[TestWith(['doesnt_start_with:foo,bar', 'Must not start with: foo, bar'])]
    #[TestWith(['doesnt_start_with', null])]
    #[TestWith(['ends_with:foo,bar', 'Must end with: foo, bar'])]
    #[TestWith(['ends_with', null])]
    #[TestWith(['doesnt_end_with:foo,bar', 'Must not end with: foo, bar'])]
    #[TestWith(['doesnt_end_with', null])]
    #[TestWith(['in_array:other.*', 'Must be a value from other.*'])]
    #[TestWith(['in_array', null])]
    public function it_only_describes_a_comparison_rule_when_it_was_given_parameters(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['in:draft,published,archived', 'Must be one of: draft, published, archived'])]
    #[TestWith(['in', null])]
    public function in_builds_an_enum_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['contains:a,b', 'Must contain: a, b'])]
    #[TestWith(['contains', null])]
    public function contains_defaults_to_an_array_type_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(ArrayType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['doesnt_contain:a,b', 'Must not contain: a, b'])]
    #[TestWith(['doesnt_contain', null])]
    public function doesnt_contain_defaults_to_an_array_type_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(ArrayType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['date_format:Y-m-d', 'Must match date format: Y-m-d'])]
    #[TestWith(['date_format:Y-m-d,d/m/Y', 'Must match one of date formats: Y-m-d, d/m/Y'])]
    #[TestWith(['date_format', null])]
    public function date_format_only_describes_the_format_when_it_was_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith([['string', 'between:2,10'], StringType::class, 'Must be between 2 and 10 characters'])]
    #[TestWith([['array', 'between:1,5'], ArrayType::class, 'Must have between 1 and 5 items'])]
    #[TestWith([['integer', 'between:1,5'], IntegerType::class, 'Must be between 1 and 5'])]
    public function between_describes_the_bounds_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    #[Test]
    #[TestWith([['string', 'max:10'], StringType::class, 'Maximum length: 10 characters'])]
    #[TestWith([['array', 'max:5'], ArrayType::class, 'Maximum items: 5'])]
    #[TestWith([['integer', 'max:100'], IntegerType::class, 'Maximum value: 100'])]
    public function max_describes_the_bound_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    #[Test]
    #[TestWith([['string', 'min:2'], StringType::class, 'Minimum length: 2 characters'])]
    #[TestWith([['array', 'min:1'], ArrayType::class, 'Minimum items: 1'])]
    #[TestWith([['integer', 'min:0'], IntegerType::class, 'Minimum value: 0'])]
    public function min_describes_the_bound_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    #[Test]
    #[TestWith([['string', 'size:5'], StringType::class, 'Must be exactly 5 characters'])]
    #[TestWith([['array', 'size:3'], ArrayType::class, 'Must contain exactly 3 items'])]
    #[TestWith([['integer', 'size:7'], IntegerType::class, 'Must be exactly 7'])]
    public function size_describes_the_exact_size_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    /**
     * validateMax()/validateMin()/validateSize()/validateBetween() called
     * ->min()/->max() straight on whatever type was already built for the
     * attribute. A boolean field combined with a bound rule (e.g.
     * ['boolean', 'max:1']) crashed with "Call to undefined method
     * BooleanType::max()" instead of just describing the rule.
     *
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['boolean', 'max:1']], 'max')]
    #[TestWith([['boolean', 'min:1']], 'min')]
    #[TestWith([['boolean', 'size:1']], 'size')]
    #[TestWith([['boolean', 'between:1,5']], 'between')]
    public function a_bound_rule_on_a_boolean_type_is_described_instead_of_crashing(array $rules): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf(BooleanType::class, $result['field']);

        $serialized = $result['field']->toArray();
        $this->assertArrayHasKey('description', $serialized);
        $this->assertArrayNotHasKey('minimum', $serialized);
        $this->assertArrayNotHasKey('maximum', $serialized);
    }

    #[Test]
    public function required_marks_the_attribute_as_required_and_describes_it(): void
    {
        $result = $this->convert(['field' => ['required']]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], 'This field is required');
        $this->assertTrue($this->isRequired($result['field']));
    }

    #[Test]
    public function a_field_without_a_required_rule_is_not_marked_as_required(): void
    {
        $result = $this->convert(['field' => ['string']]);

        $this->assertFalse($this->isRequired($result['field']));
    }

    #[Test]
    public function a_wildcard_rule_types_the_parent_arrays_items(): void
    {
        $result = $this->convert([
            'tags' => ['array'],
            'tags.*' => ['string'],
        ]);

        $this->assertInstanceOf(ArrayType::class, $result['tags']);

        $serialized = $result['tags']->toArray();

        $this->assertArrayHasKey('items', $serialized);
        $this->assertSame('string', $serialized['items']['type']);
    }

    #[Test]
    public function a_wildcard_rule_without_a_typed_parent_is_ignored(): void
    {
        $result = $this->convert([
            'tags.*' => ['string'],
        ]);

        $this->assertArrayNotHasKey('tags', $result);
    }

    /**
     * processWildcardRules() used to foreach() the raw rules for a wildcard
     * attribute directly, without normalising a pipe-delimited rule string
     * into an array first - "foreach() argument must be of type array|object,
     * string given" for any 'field.*' => 'string|max:5' shaped rule set.
     */
    #[Test]
    #[TestWith(['string|max:5'], 'pipe_string')]
    #[TestWith([['string', 'max:5']], 'array')]
    public function a_wildcard_rule_given_as_a_pipe_string_does_not_crash(array|string $rules): void
    {
        $result = $this->convert([
            'tags' => ['array'],
            'tags.*' => $rules,
        ]);

        $this->assertInstanceOf(ArrayType::class, $result['tags']);

        $serialized = $result['tags']->toArray();
        $this->assertArrayHasKey('items', $serialized);
        $this->assertSame('string', $serialized['items']['type']);
    }

    #[Test]
    public function a_closure_rule_describes_a_custom_validation_closure(): void
    {
        // Field::guessFieldType() calls buildTypeFromRules() directly with a
        // repository field's raw rules, bypassing validator()->make()->getRules(),
        // which is what wraps a Closure into a ClosureValidationRule instead.
        $action = new JsonSchemaFromRulesAction;
        $schema = new JsonSchemaTypeFactory;

        $type = $action->buildTypeFromRules($schema, 'field', [
            function (string $attribute, mixed $value, Closure $fail): void {},
        ]);

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertDescribed($type, 'This field uses a custom validation closure.');
    }

    #[Test]
    public function a_repeated_rule_object_reuses_the_type_already_built_for_the_attribute(): void
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new JsonSchemaTypeFactory;

        $result = $action($schema, [
            'field' => [Password::min(8), Password::min(8)],
        ]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], 'This field must be a valid password.');
    }

    #[Test]
    public function an_empty_rule_string_builds_no_type(): void
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new JsonSchemaTypeFactory;

        $this->assertNull($action->buildTypeFromRule($schema, 'field', ''));
    }

    #[Test]
    public function an_unknown_rule_name_builds_no_type(): void
    {
        $result = $this->convert(['field' => ['this_rule_does_not_exist_anywhere']]);

        $this->assertArrayNotHasKey('field', $result);
    }

    #[Test]
    #[TestWith([ArrayType::class, 'array'])]
    #[TestWith([BooleanType::class, 'boolean'])]
    #[TestWith([IntegerType::class, 'integer'])]
    #[TestWith([NumberType::class, 'number'])]
    #[TestWith([ObjectType::class, 'object'])]
    #[TestWith([StringType::class, 'string'])]
    public function it_maps_a_schema_type_to_its_primitive_name(string $typeClass, string $primitive): void
    {
        $schema = new JsonSchemaTypeFactory;

        $type = match ($typeClass) {
            ArrayType::class => $schema->array(),
            BooleanType::class => $schema->boolean(),
            IntegerType::class => $schema->integer(),
            NumberType::class => $schema->number(),
            ObjectType::class => $schema->object(),
            default => $schema->string(),
        };

        $this->assertSame($primitive, JsonSchemaFromRulesAction::getPrimitiveTypeFromSchemaType($type));
    }

    /**
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, Type>
     */
    private function convert(array $rules): array
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new JsonSchemaTypeFactory;

        return $action($schema, $rules);
    }

    private function assertDescribed(Type $type, ?string $descriptionContains): void
    {
        $serialized = $type->toArray();

        if ($descriptionContains === null) {
            $this->assertArrayNotHasKey('description', $serialized);

            return;
        }

        $this->assertArrayHasKey('description', $serialized);
        $this->assertStringContainsString($descriptionContains, $serialized['description']);
    }

    private function isRequired(Type $type): bool
    {
        $reflection = new ReflectionProperty($type, 'required');
        $reflection->setAccessible(true);

        return (bool) $reflection->getValue($type);
    }
}
