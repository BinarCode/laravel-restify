<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class FieldSchemaValidationTest extends IntegrationTestCase
{
    public function test_field_rules_convert_to_correct_schema_types(): void
    {
        //        // Test string field type detection
        $titleField = Field::make('title')->rules(['required', 'string', 'max:255']);
        $this->assertEquals('string', $titleField->guessFieldType());

        // Test integer field type detection - debug first
        $priorityField = Field::make('priority')->rules(['required', 'integer', 'min:1']);

        // Debug what rules are actually set
        $reflection = new \ReflectionProperty($priorityField, 'rules');
        $reflection->setAccessible(true);
        $actualRules = $reflection->getValue($priorityField);

        // This should help us understand what's happening
        $this->assertContains('integer', $actualRules, 'Integer rule not found in: '.json_encode($actualRules));
        $this->assertEquals('number', $priorityField->guessFieldType());

        // Test boolean field type detection
        $publishedField = Field::make('is_published')->rules(['boolean']);
        $this->assertEquals('boolean', $publishedField->guessFieldType());

        // Test numeric field type detection
        $ratingField = Field::make('rating')->rules(['numeric', 'between:0,5']);
        $this->assertEquals('number', $ratingField->guessFieldType());

        // Test email field (should be string type)
        $emailField = Field::make('author_email')->rules(['required', 'email']);
        $this->assertEquals('string', $emailField->guessFieldType());

        // Test array field type detection
        $tagsField = Field::make('tags')->rules(['array']);
        $this->assertEquals('array', $tagsField->guessFieldType()); // Arrays converted to strings

        // Test default type for custom validation
        $slugField = Field::make('slug')->rules(['required', 'unique:posts,slug']);
        $this->assertEquals('string', $slugField->guessFieldType());
    }

    public function test_field_validation_rules_format(): void
    {
        // Test field with 'in' validation
        $statusField = Field::make('status')->rules(['required', 'string', 'in:draft,published,archived']);
        $this->assertEquals('string', $statusField->guessFieldType());

        // Test field with min/max rules
        $wordCountField = Field::make('word_count')->rules(['integer', 'min:100', 'max:5000']);
        $this->assertEquals('number', $wordCountField->guessFieldType());

        // Test numeric field with between rule
        $ratingField = Field::make('rating')->rules(['numeric', 'between:1,10']);
        $this->assertEquals('number', $ratingField->guessFieldType());

        // Test required field detection
        $requiredField = Field::make('name')->rules(['required', 'string']);
        $reflectionMethod = new \ReflectionMethod($requiredField, 'isRequired');
        $reflectionMethod->setAccessible(true);
        $this->assertTrue($reflectionMethod->invoke($requiredField));

        // Test optional field detection
        $optionalField = Field::make('description')->rules(['sometimes', 'string']);
        $this->assertFalse($reflectionMethod->invoke($optionalField));
    }
}
