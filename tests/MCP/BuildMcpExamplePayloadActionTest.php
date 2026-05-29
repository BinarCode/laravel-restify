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

    public function test_bind_values_override_placeholders(): void
    {
        $factory = new JsonSchemaTypeFactory;
        $schema = [
            'campaign_id' => $factory->string()->required(),
            'lead_stage_id' => $factory->integer()->required(),
            'context' => $factory->string(),
        ];

        $payload = (new BuildMcpExamplePayloadAction)($schema, bind: [
            'campaign_id' => '01krb179j1ncmjpbggah5gcwvb',
            'lead_stage_id' => 2,
        ]);

        $this->assertSame([
            'campaign_id' => '01krb179j1ncmjpbggah5gcwvb',
            'lead_stage_id' => 2,
            'context' => '<string, optional>',
        ], $payload);
    }

    public function test_nests_dotted_keys_under_their_parent(): void
    {
        $factory = new JsonSchemaTypeFactory;
        $schema = [
            'campaign_id' => $factory->string()->required(),
            'length' => $factory->array(),
            'length.subject_max' => $factory->integer(),
            'length.body_max' => $factory->integer(),
        ];

        $payload = (new BuildMcpExamplePayloadAction)($schema, bind: []);

        $this->assertSame([
            'campaign_id' => '<string, required>',
            'length' => [
                'subject_max' => '<integer, optional>',
                'body_max' => '<integer, optional>',
            ],
        ], $payload);
    }

    public function test_nests_dotted_keys_when_children_appear_before_parent(): void
    {
        $factory = new JsonSchemaTypeFactory;
        $schema = [
            'length.subject_max' => $factory->integer(),
            'length.body_max' => $factory->integer(),
            'length' => $factory->array(),
            'campaign_id' => $factory->string()->required(),
        ];

        $payload = (new BuildMcpExamplePayloadAction)($schema, bind: []);

        $this->assertSame([
            'length' => [
                'subject_max' => '<integer, optional>',
                'body_max' => '<integer, optional>',
            ],
            'campaign_id' => '<string, required>',
        ], $payload);
    }
}
