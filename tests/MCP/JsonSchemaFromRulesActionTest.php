<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Actions\JsonSchemaFromRulesAction;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

/**
 * @requires Laravel 12+
 */
class JsonSchemaFromRulesActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! interface_exists(\Illuminate\Contracts\JsonSchema\JsonSchema::class)) {
            $this->markTestSkipped('JsonSchema classes are only available in Laravel 12+');
        }
    }

    public function test_before_date_rule_generates_correct_schema(): void
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new \Illuminate\JsonSchema\JsonSchemaTypeFactory;

        $rules = [
            'event_date' => ['required', 'date', 'before:2025-12-31'],
        ];

        $result = $action($schema, $rules);

        $this->assertArrayHasKey('event_date', $result);
        $this->assertInstanceOf(\Illuminate\JsonSchema\Types\StringType::class, $result['event_date']);

        $serialized = $result['event_date']->toArray();

        $this->assertEquals('string', $serialized['type']);
        $this->assertArrayHasKey('description', $serialized);
        $this->assertStringContainsString('Must be before: 2025-12-31', $serialized['description']);
    }

    public function test_integer_rule_generates_correct_schema(): void
    {
        $action = new JsonSchemaFromRulesAction;
        $schema = new \Illuminate\JsonSchema\JsonSchemaTypeFactory;

        $rules = [
            'age' => ['required', 'integer', 'min:18'],
        ];

        $result = $action($schema, $rules);

        $this->assertArrayHasKey('age', $result);
        $this->assertInstanceOf(\Illuminate\JsonSchema\Types\IntegerType::class, $result['age']);

        $serialized = $result['age']->toArray();

        $this->assertEquals('integer', $serialized['type']);
        $this->assertEquals(18, $serialized['minimum']);

        $reflection = new \ReflectionProperty($result['age'], 'required');
        $this->assertTrue($reflection->getValue($result['age']));
    }
}
