<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Actions\BuildMcpExamplePayloadAction;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use PHPUnit\Framework\TestCase;

class BuildMcpExamplePayloadActionTest extends TestCase
{
    public function test_emits_type_hint_placeholders_for_flat_schema(): void
    {
        $factory = new JsonSchemaTypeFactory;
        $schema = [
            'campaign_id' => $factory->string()->required(),
            'lead_stage_id' => $factory->integer()->required(),
            'context' => $factory->string(),
        ];

        $payload = (new BuildMcpExamplePayloadAction)($schema, bind: []);

        $this->assertSame([
            'campaign_id' => '<string, required>',
            'lead_stage_id' => '<integer, required>',
            'context' => '<string, optional>',
        ], $payload);
    }

    public function test_nullable_type_renders_union_placeholder(): void
    {
        $factory = new JsonSchemaTypeFactory;
        $schema = [
            'notes' => $factory->string()->nullable(),
        ];

        $payload = (new BuildMcpExamplePayloadAction)($schema, bind: []);

        $this->assertSame([
            'notes' => '<string|null, optional>',
        ], $payload);
    }
}
