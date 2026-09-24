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

/**
 * The SchemaAttributes trait mirrors Laravel's ValidatesAttributes rule set,
 * but every validateXxx() method returns a JSON Schema Type (with a human
 * readable description) instead of a boolean, so an MCP tool's input schema
 * can describe a repository field's validation rules. JsonSchemaFromRulesAction
 * is the only entry point that dispatches into it, exactly like it is used by
 * Action::jsonSchema(), Getter::jsonSchema() and FieldMcpSchemaDetection.
 *
 * This matrix calls JsonSchemaFromRulesAction directly - it does not go
 * through Field::guessFieldType() or an MCP tool's built schema. That path is
 * covered separately by CurrentPasswordSchemaRuleTest and FieldSchemaValidationTest.
 */
class SchemaAttributesTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['accepted', null], 'accepted')]
    #[TestWith(['accepted_if:other,value', 'Must be accepted when another attribute has a given value'], 'accepted_if')]
    #[TestWith(['declined', 'Must be declined (no, off, 0, false)'], 'declined')]
    #[TestWith(['declined_if:other,value', 'Must be declined when another attribute has a given value'], 'declined_if')]
    #[TestWith(['active_url', 'Must be an active URL with valid DNS records'], 'active_url')]
    #[TestWith(['ascii', 'Must be 7 bit ASCII'], 'ascii')]
    #[TestWith(['bail', null], 'bail')]
    #[TestWith(['confirmed', 'Must have a matching confirmation field'], 'confirmed')]
    #[TestWith(['decimal:2', 'Must have a specific number of decimal places'], 'decimal')]
    #[TestWith(['different:other', 'Must be different from another attribute'], 'different')]
    #[TestWith(['digits:4', 'Must have a specific number of digits'], 'digits')]
    #[TestWith(['digits_between:1,4', 'Must have digits between a range'], 'digits_between')]
    #[TestWith(['dimensions:min_width=100', 'Image must match specified dimensions'], 'dimensions')]
    #[TestWith(['distinct', 'Must be unique among other values'], 'distinct')]
    #[TestWith(['extensions:pdf,doc', 'Must be a valid file with allowed extensions'], 'extensions')]
    #[TestWith(['filled', 'Must be filled when present'], 'filled')]
    #[TestWith(['gt:other', 'Must be greater than another attribute'], 'gt')]
    #[TestWith(['lt:other', 'Must be less than another attribute'], 'lt')]
    #[TestWith(['gte:other', 'Must be greater than or equal to another attribute'], 'gte')]
    #[TestWith(['lte:other', 'Must be less than or equal to another attribute'], 'lte')]
    #[TestWith(['lowercase', 'Must be lowercase'], 'lowercase')]
    #[TestWith(['uppercase', 'Must be uppercase'], 'uppercase')]
    #[TestWith(['hex_color', 'Must be a valid HEX color'], 'hex_color')]
    #[TestWith(['max_digits:5', 'Must have a maximum number of digits'], 'max_digits')]
    #[TestWith(['min_digits:2', 'Must have a minimum number of digits'], 'min_digits')]
    #[TestWith(['missing', 'Must be missing from the data'], 'missing')]
    #[TestWith(['missing_if:other,value', 'Must be missing when another attribute has a given value'], 'missing_if')]
    #[TestWith(['missing_unless:other,value', 'Must be missing unless another attribute has a given value'], 'missing_unless')]
    #[TestWith(['missing_with:other', 'Must be missing when any given attribute is present'], 'missing_with')]
    #[TestWith(['missing_with_all:other,another', 'Must be missing when all given attributes are present'], 'missing_with_all')]
    #[TestWith(['not_in:a,b', 'Must not be one of the specified values'], 'not_in')]
    #[TestWith(['present', 'Must be present in the data'], 'present')]
    #[TestWith(['present_if:other,value', 'Must be present when another attribute has a given value'], 'present_if')]
    #[TestWith(['present_unless:other,value', 'Must be present unless another attribute has a given value'], 'present_unless')]
    #[TestWith(['present_with:other', 'Must be present when any given attribute is present'], 'present_with')]
    #[TestWith(['present_with_all:other,another', 'Must be present when all given attributes are present'], 'present_with_all')]
    #[TestWith(['regex:/^[a-z]+$/', 'Must match the specified regular expression'], 'regex')]
    #[TestWith(['not_regex:/^[a-z]+$/', 'Must not match the specified regular expression'], 'not_regex')]
    #[TestWith(['required_if_accepted:other', 'Must be present when another attribute is accepted'], 'required_if_accepted')]
    #[TestWith(['required_if_declined:other', 'Must be present when another attribute is declined'], 'required_if_declined')]
    #[TestWith(['prohibited', 'Must not be present or must be empty'], 'prohibited')]
    #[TestWith(['prohibited_if:other,value', 'Must not be present when another attribute has a given value'], 'prohibited_if')]
    #[TestWith(['prohibited_if_accepted:other', 'Must not be present when another attribute is accepted'], 'prohibited_if_accepted')]
    #[TestWith(['prohibited_if_declined:other', 'Must not be present when another attribute is declined'], 'prohibited_if_declined')]
    #[TestWith(['prohibited_unless:other,value', 'Must not be present unless another attribute has a given value'], 'prohibited_unless')]
    #[TestWith(['prohibits:other', 'Prohibits other specified attributes from being present'], 'prohibits')]
    #[TestWith(['exclude', 'This attribute is excluded from validation'], 'exclude')]
    #[TestWith(['exclude_if:other,value', 'This attribute is excluded when another attribute has a given value'], 'exclude_if')]
    #[TestWith(['exclude_unless:other,value', 'This attribute is excluded unless another attribute has a given value'], 'exclude_unless')]
    #[TestWith(['required_unless:other,value', null], 'required_unless')]
    #[TestWith(['exclude_with:other', 'This attribute is excluded when another attribute is present'], 'exclude_with')]
    #[TestWith(['exclude_without:other', 'This attribute is excluded when another attribute is missing'], 'exclude_without')]
    #[TestWith(['required_with:other', 'Must be present when any other attribute exists'], 'required_with')]
    #[TestWith(['required_with_all:other,another', 'Must be present when all other attributes exist'], 'required_with_all')]
    #[TestWith(['required_without:other', 'Must be present when another attribute does not exist'], 'required_without')]
    #[TestWith(['required_without_all:other,another', 'Must be present when all other attributes do not exist'], 'required_without_all')]
    #[TestWith(['sometimes', null], 'sometimes')]
    #[TestWith(['required_if:other,value', null], 'required_if')]
    #[TestWith(['exists:users,id', 'Must exist in the database'], 'exists')]
    public function it_converts_a_rule_with_no_type_detection_into_a_described_string_schema(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['alpha', StringType::class, 'Must contain only alphabetic characters'], 'alpha')]
    #[TestWith(['alpha_dash', StringType::class, 'Must contain only alpha-numeric characters, dashes, and underscores'], 'alpha_dash')]
    #[TestWith(['alpha_num', StringType::class, 'Must contain only alpha-numeric characters'], 'alpha_num')]
    #[TestWith(['array', ArrayType::class, 'Must be an array'], 'array')]
    #[TestWith(['boolean', BooleanType::class, 'Must be a boolean (true/false)'], 'boolean')]
    #[TestWith(['date', StringType::class, 'Must be a valid date format.'], 'date')]
    #[TestWith(['email', StringType::class, 'Must be a valid email address.'], 'email')]
    #[TestWith(['file', StringType::class, 'Must be a valid file, or file absolute path'], 'file')]
    #[TestWith(['image', StringType::class, 'Must be a valid image file'], 'image')]
    #[TestWith(['ip', StringType::class, 'Must be a valid IP address'], 'ip')]
    #[TestWith(['ipv4', StringType::class, 'Must be a valid IPv4 address'], 'ipv4')]
    #[TestWith(['ipv6', StringType::class, 'Must be a valid IPv6 address'], 'ipv6')]
    #[TestWith(['mac_address', StringType::class, 'Must be a valid MAC address'], 'mac_address')]
    #[TestWith(['json', StringType::class, 'Must be valid JSON'], 'json')]
    #[TestWith(['timezone', StringType::class, 'Must be a valid timezone'], 'timezone')]
    #[TestWith(['url', StringType::class, 'Must be a valid URL'], 'url')]
    #[TestWith(['ulid', StringType::class, 'Must be a valid ULID'], 'ulid')]
    #[TestWith(['uuid', StringType::class, 'Must be a valid UUID'], 'uuid')]
    #[TestWith(['integer', IntegerType::class, 'Must be an integer'], 'integer')]
    #[TestWith(['numeric', NumberType::class, 'Must be a numeric value'], 'numeric')]
    #[TestWith(['string', StringType::class, 'Must be a string'], 'string')]
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
    #[TestWith(['before:2025-01-01', 'This is a date attribute. Must be before: 2025-01-01'], 'before')]
    #[TestWith(['before', null], 'before_without_value')]
    #[TestWith(['before_or_equal:2025-01-01', 'Must be before or equal to: 2025-01-01'], 'before_or_equal')]
    #[TestWith(['before_or_equal', null], 'before_or_equal_without_value')]
    #[TestWith(['after:2025-01-01', 'Date attribute, must be after: 2025-01-01'], 'after')]
    #[TestWith(['after', null], 'after_without_value')]
    #[TestWith(['after_or_equal:2025-01-01', 'Must be after or equal to: 2025-01-01'], 'after_or_equal')]
    #[TestWith(['after_or_equal', null], 'after_or_equal_without_value')]
    #[TestWith(['date_equals:2025-01-01', 'Must be equal to date: 2025-01-01'], 'date_equals')]
    #[TestWith(['date_equals', null], 'date_equals_without_value')]
    #[TestWith(['same:other', 'Must match: other'], 'same')]
    #[TestWith(['same', null], 'same_without_value')]
    #[TestWith(['starts_with:foo,bar', 'Must start with: foo, bar'], 'starts_with')]
    #[TestWith(['starts_with', null], 'starts_with_without_value')]
    #[TestWith(['doesnt_start_with:foo,bar', 'Must not start with: foo, bar'], 'doesnt_start_with')]
    #[TestWith(['doesnt_start_with', null], 'doesnt_start_with_without_value')]
    #[TestWith(['ends_with:foo,bar', 'Must end with: foo, bar'], 'ends_with')]
    #[TestWith(['ends_with', null], 'ends_with_without_value')]
    #[TestWith(['doesnt_end_with:foo,bar', 'Must not end with: foo, bar'], 'doesnt_end_with')]
    #[TestWith(['doesnt_end_with', null], 'doesnt_end_with_without_value')]
    #[TestWith(['in_array:other.*', 'Must be a value from other.*'], 'in_array')]
    #[TestWith(['in_array', null], 'in_array_without_value')]
    public function it_only_describes_a_comparison_rule_when_it_was_given_parameters(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['in:draft,published,archived', 'Must be one of: draft, published, archived'], 'with_values')]
    #[TestWith(['in', null], 'without_values')]
    public function in_builds_an_enum_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    /**
     * @param  list<string>  $rules
     * @param  list<int|float|bool|string>  $expectedEnum
     */
    #[Test]
    #[TestWith([['integer', 'in:1,2,3'], IntegerType::class, [1, 2, 3]], 'integer')]
    #[TestWith([['numeric', 'in:1.5,2.5'], NumberType::class, [1.5, 2.5]], 'number')]
    #[TestWith([['boolean', 'in:1,0'], BooleanType::class, [true, false]], 'boolean')]
    #[TestWith([['string', 'in:a,b'], StringType::class, ['a', 'b']], 'string')]
    public function in_casts_the_enum_values_to_the_attributes_existing_type(array $rules, string $expectedType, array $expectedEnum): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertSame($expectedEnum, $result['field']->toArray()['enum']);
    }

    #[Test]
    #[TestWith(['contains:a,b', 'Must contain: a, b'], 'with_values')]
    #[TestWith(['contains', null], 'without_values')]
    public function contains_defaults_to_an_array_type_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(ArrayType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['doesnt_contain:a,b', 'Must not contain: a, b'], 'with_values')]
    #[TestWith(['doesnt_contain', null], 'without_values')]
    public function doesnt_contain_defaults_to_an_array_type_and_only_describes_it_when_values_are_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(ArrayType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    #[TestWith(['list', 'Must be a list (sequential array)'], 'list')]
    #[TestWith(['required_array_keys:a,b', 'Array must have all required keys'], 'required_array_keys')]
    #[TestWith(['in_array_keys:a,b', 'Array must have at least one of the specified keys'], 'in_array_keys')]
    public function array_shaped_rules_default_to_an_array_schema_instead_of_a_string(string $rule, string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(ArrayType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    #[Test]
    public function nullable_marks_the_type_nullable_instead_of_claiming_it_is_optional(): void
    {
        $result = $this->convert(['field' => ['string', 'nullable']]);

        $this->assertInstanceOf(StringType::class, $result['field']);

        $serialized = $result['field']->toArray();

        $this->assertSame(['string', 'null'], $serialized['type']);
        $this->assertStringNotContainsStringIgnoringCase('optional', $serialized['description']);
    }

    #[Test]
    public function multiple_of_without_a_prior_type_builds_a_number_schema(): void
    {
        $result = $this->convert(['field' => ['multiple_of:2']]);

        $this->assertInstanceOf(NumberType::class, $result['field']);

        $serialized = $result['field']->toArray();
        $this->assertSame(2, $serialized['multipleOf']);
        $this->assertStringContainsString('Must be a multiple of 2', $serialized['description']);
    }

    #[Test]
    public function multiple_of_keeps_an_existing_integer_type(): void
    {
        $result = $this->convert(['field' => ['integer', 'multiple_of:5']]);

        $this->assertInstanceOf(IntegerType::class, $result['field']);
        $this->assertSame(5, $result['field']->toArray()['multipleOf']);
    }

    #[Test]
    #[TestWith(['date_format:Y-m-d', 'Must match date format: Y-m-d'], 'single_format')]
    #[TestWith(['date_format:Y-m-d,d/m/Y', 'Must match one of date formats: Y-m-d, d/m/Y'], 'multiple_formats')]
    #[TestWith(['date_format', null], 'without_value')]
    public function date_format_only_describes_the_format_when_it_was_given(string $rule, ?string $descriptionContains): void
    {
        $result = $this->convert(['field' => [$rule]]);

        $this->assertInstanceOf(StringType::class, $result['field']);
        $this->assertDescribed($result['field'], $descriptionContains);
    }

    /**
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['string', 'between:2,10'], StringType::class, 'Must be between 2 and 10 characters'], 'string')]
    #[TestWith([['array', 'between:1,5'], ArrayType::class, 'Must have between 1 and 5 items'], 'array')]
    #[TestWith([['integer', 'between:1,5'], IntegerType::class, 'Must be between 1 and 5'], 'integer')]
    public function between_describes_the_bounds_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    #[Test]
    public function between_does_not_truncate_a_number_types_decimal_bounds(): void
    {
        $result = $this->convert(['field' => ['numeric', 'between:1.5,9.5']]);

        $this->assertInstanceOf(NumberType::class, $result['field']);

        $serialized = $result['field']->toArray();
        $this->assertSame(1.5, $serialized['minimum']);
        $this->assertSame(9.5, $serialized['maximum']);
    }

    /**
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['string', 'max:10'], StringType::class, 'Maximum length: 10 characters'], 'string')]
    #[TestWith([['array', 'max:5'], ArrayType::class, 'Maximum items: 5'], 'array')]
    #[TestWith([['integer', 'max:100'], IntegerType::class, 'Maximum value: 100'], 'integer')]
    public function max_describes_the_bound_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    /**
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['string', 'min:2'], StringType::class, 'Minimum length: 2 characters'], 'string')]
    #[TestWith([['array', 'min:1'], ArrayType::class, 'Minimum items: 1'], 'array')]
    #[TestWith([['integer', 'min:0'], IntegerType::class, 'Minimum value: 0'], 'integer')]
    public function min_describes_the_bound_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    /**
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['string', 'size:5'], StringType::class, 'Must be exactly 5 characters'], 'string')]
    #[TestWith([['array', 'size:3'], ArrayType::class, 'Must contain exactly 3 items'], 'array')]
    #[TestWith([['integer', 'size:7'], IntegerType::class, 'Must be exactly 7'], 'integer')]
    public function size_describes_the_exact_size_according_to_the_attributes_existing_type(array $rules, string $expectedType, string $description): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf($expectedType, $result['field']);
        $this->assertDescribed($result['field'], $description);
    }

    /**
     * @param  list<string>  $rules
     */
    #[Test]
    #[TestWith([['numeric', 'max:9.99'], 9.99], 'max')]
    #[TestWith([['numeric', 'min:0.5'], 0.5], 'min')]
    #[TestWith([['numeric', 'size:9.99'], 9.99], 'size')]
    public function bound_rules_do_not_truncate_a_number_types_decimal_value(array $rules, float $expectedBound): void
    {
        $result = $this->convert(['field' => $rules]);

        $this->assertInstanceOf(NumberType::class, $result['field']);

        $serialized = $result['field']->toArray();
        $bound = $serialized['maximum'] ?? $serialized['minimum'];

        $this->assertSame($expectedBound, $bound);
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

        $object = (new JsonSchemaTypeFactory)->object(['field' => $result['field']])->toArray();
        $this->assertContains('field', $object['required']);
    }

    #[Test]
    public function a_field_without_a_required_rule_is_not_marked_as_required(): void
    {
        $result = $this->convert(['field' => ['string']]);

        $object = (new JsonSchemaTypeFactory)->object(['field' => $result['field']])->toArray();
        $this->assertArrayNotHasKey('required', $object);
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

    /**
     * Known gap (see "Known, not changed" in the PR body): processWildcardRules()
     * only matches an attribute that ends in exactly ".*" (a direct array of
     * scalars). A nested wildcard such as "items.*.name" is never wired onto
     * the parent's `items` schema - growee's StoreExpensesRestifyAction rules
     * ('expenses.*.amount') hit exactly this shape.
     */
    #[Test]
    public function a_nested_wildcard_rule_is_not_wired_onto_the_parent_items_schema(): void
    {
        $result = $this->convert([
            'items' => ['array'],
            'items.*.name' => ['string'],
        ]);

        $this->assertInstanceOf(ArrayType::class, $result['items']);
        $this->assertArrayNotHasKey('items', $result['items']->toArray());
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
    #[TestWith([ArrayType::class, 'array'], 'array')]
    #[TestWith([BooleanType::class, 'boolean'], 'boolean')]
    #[TestWith([IntegerType::class, 'integer'], 'integer')]
    #[TestWith([NumberType::class, 'number'], 'number')]
    #[TestWith([ObjectType::class, 'object'], 'object')]
    #[TestWith([StringType::class, 'string'], 'string')]
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
     * @param  array<string, array<int, mixed>|string>  $rules
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
}
