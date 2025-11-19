<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\StringType;

class FieldSchemaValidationTest extends IntegrationTestCase
{
    public function test_field_rules_convert_to_correct_schema_types(): void
    {
        // Test string field type detection
        $titleField = Field::make('title')->rules(['required', 'string', 'max:255']);
        $this->assertInstanceOf(StringType::class, $titleField->guessFieldType(new McpStoreRequest));

        // Test integer field type detection
        $priorityField = Field::make('priority')->rules(['required', 'integer', 'min:1']);
        $this->assertInstanceOf(IntegerType::class, $priorityField->guessFieldType(new McpStoreRequest));

        // Test boolean field type detection
        $publishedField = Field::make('is_published')->rules(['boolean']);
        $this->assertInstanceOf(BooleanType::class, $publishedField->guessFieldType(new McpStoreRequest));

        // Test numeric field type detection
        $ratingField = Field::make('rating')->rules(['numeric', 'between:0,5']);
        $this->assertInstanceOf(NumberType::class, $ratingField->guessFieldType(new McpStoreRequest));

        // Test email field (should be string type)
        $emailField = Field::make('author_email')->rules(['required', 'email']);
        $this->assertInstanceOf(StringType::class, $emailField->guessFieldType(new McpStoreRequest));

        // Test array field type detection
        $tagsField = Field::make('tags')->rules(['array']);
        $this->assertInstanceOf(ArrayType::class, $tagsField->guessFieldType(new McpStoreRequest));

        // Test default type for custom validation
        $slugField = Field::make('slug')->rules(['required', 'unique:posts,slug']);
        $this->assertInstanceOf(StringType::class, $slugField->guessFieldType(new McpStoreRequest));
    }

    public function test_field_validation_rules_format(): void
    {
        // Test field with 'in' validation
        $statusField = Field::make('status')->rules(['required', 'string', 'in:draft,published,archived']);
        $this->assertInstanceOf(StringType::class, $statusField->guessFieldType(new McpStoreRequest));

        // Test field with min/max rules
        $wordCountField = Field::make('word_count')->rules(['integer', 'min:100', 'max:5000']);
        $this->assertInstanceOf(IntegerType::class, $wordCountField->guessFieldType(new McpStoreRequest));

        // Test numeric field with between rule
        $ratingField = Field::make('rating')->rules(['numeric', 'between:1,10']);
        $this->assertInstanceOf(NumberType::class, $ratingField->guessFieldType(new McpStoreRequest));

        // Test required field detection
        $requiredField = Field::make('name')->rules(['required', 'string']);
        $reflectionMethod = new \ReflectionMethod($requiredField, 'isRequired');
        $reflectionMethod->setAccessible(true);
        $this->assertTrue($reflectionMethod->invoke($requiredField));

        // Test optional field detection
        $optionalField = Field::make('description')->rules(['sometimes', 'string']);
        $this->assertFalse($reflectionMethod->invoke($optionalField));
    }

    public function test_field_json_schema_has_description(): void
    {
        $request = new McpStoreRequest;
        $schemaFactory = new JsonSchemaTypeFactory;
        $repository = PostRepository::partialMock();
        $field = field('published_at')->rules(['nullable', 'date', 'after:2020-01-01'])->resolveJsonSchema(
            $schemaFactory,
            $request,
            $repository
        );

        $schema = $field->jsonSchema()->toArray();
        $this->assertSame('Date attribute, must be after: 2020-01-01', $schema['description']);
    }

    public function test_field_json_schema_prioritize_user_description(): void
    {
        $request = new McpStoreRequest;
        $schemaFactory = new JsonSchemaTypeFactory;
        $repository = PostRepository::partialMock();
        $field = field('published_at')->rules(['nullable', 'date', 'after:2020-01-01'])
            ->description('This is a custom description.')
            ->resolveJsonSchema(
                $schemaFactory,
                $request,
                $repository
            );

        $schema = $field->jsonSchema()->toArray();
        $this->assertSame('This is a custom description.', $schema['description']);
    }

    public function test_can_validate_custom_rule(): void
    {
        $field = field('published_at')->rules([new UniqueClientCompanyNameRule]);

        $type = $field->guessFieldType(new McpStoreRequest);
        $this->assertInstanceOf(StringType::class, $type);
    }
}
class UniqueClientCompanyNameRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void {}
}
