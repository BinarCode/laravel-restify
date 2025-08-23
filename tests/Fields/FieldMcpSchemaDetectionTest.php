<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\File;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\MCP\Concerns\FieldMcpSchemaDetection;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Mockery;

class FieldMcpSchemaDetectionTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_tool_schema_with_default_implementation(): void
    {
        $schema = Mockery::mock(ToolInputSchema::class);
        $repository = new PostRepository();

        $schema->shouldReceive('string')->with('title')->once()->andReturnSelf();
        $schema->shouldReceive('description')->with('Field: title (type: string). Examples: Sample Title, My Title')->once()->andReturnSelf();

        $field = $this->createTestField('title');
        $result = $field->resolveToolSchema($schema, $repository);

        $this->assertSame($field, $result);
    }

    public function test_resolve_tool_schema_with_required_field(): void
    {
        $schema = Mockery::mock(ToolInputSchema::class);
        $repository = new PostRepository();

        $schema->shouldReceive('string')->with('title')->once()->andReturnSelf();
        $schema->shouldReceive('description')->once()->andReturnSelf();
        $schema->shouldReceive('required')->once()->andReturnSelf();

        $field = $this->createTestField('title', ['required']);
        $result = $field->resolveToolSchema($schema, $repository);

        $this->assertSame($field, $result);
    }

    public function test_resolve_tool_schema_with_custom_callback(): void
    {
        $schema = Mockery::mock(ToolInputSchema::class);
        $repository = new PostRepository();
        $callbackCalled = false;

        $field = $this->createTestField('title');
        $field->toolInputSchemaCallback = function ($passedSchema, $passedRepository, $passedField) use ($schema, $repository, $field, &$callbackCalled) {
            $this->assertSame($schema, $passedSchema);
            $this->assertSame($repository, $passedRepository);
            $this->assertSame($field, $passedField);
            $callbackCalled = true;
        };

        $result = $field->resolveToolSchema($schema, $repository);

        $this->assertTrue($callbackCalled);
        $this->assertSame($field, $result);
    }

    public function test_get_string_examples_for_different_contexts(): void
    {
        $field = $this->createTestField('email');
        $examples = $field->getStringExamples('email');
        $this->assertContains('user@example.com', $examples);

        $field = $this->createTestField('name');
        $examples = $field->getStringExamples('name');
        $this->assertContains('John Doe', $examples);

        $field = $this->createTestField('url');
        $examples = $field->getStringExamples('url');
        $this->assertContains('https://example.com', $examples);

        $field = $this->createTestField('phone');
        $examples = $field->getStringExamples('phone');
        $this->assertContains('+1234567890', $examples);

        $field = $this->createTestField('random_field');
        $examples = $field->getStringExamples('random_field');
        $this->assertEquals(['sample text', 'example value'], $examples);
    }

    protected function createTestField(string $attribute, array $rules = []): Field
    {
        $field = Mockery::mock(Field::class)->makePartial();
        $field->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('computed')->andReturn(false);
        $field->shouldReceive('getStoringRules')->andReturn($rules);
        $field->shouldReceive('guessFieldType')->andReturn('string');
        $field->attribute = $attribute;
        $field->label = null;

        // Add the trait to the mock
        $field->shouldReceive('resolveToolSchema')->passthru();
        $field->shouldReceive('generateFieldDescription')->passthru();
        $field->shouldReceive('isRequired')->passthru();
        $field->shouldReceive('isRelationshipField')->passthru();
        $field->shouldReceive('formatValidationRules')->passthru();
        $field->shouldReceive('generateFieldExamples')->passthru();
        $field->shouldReceive('getNumberExamples')->passthru();
        $field->shouldReceive('getStringExamples')->passthru();

        return $field;
    }
}
