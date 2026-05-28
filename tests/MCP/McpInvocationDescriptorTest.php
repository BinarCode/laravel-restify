<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\McpInvocationDescriptor;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class McpInvocationDescriptorTest extends IntegrationTestCase
{
    public function test_to_array_returns_expected_shape(): void
    {
        $descriptor = new McpInvocationDescriptor(
            toolName: 'leads-generate-message-for-lead-action-tool',
            repositoryUriKey: 'leads',
            actionUriKey: 'generate-message-for-lead',
            description: 'Generate a personalized message.',
            route: 'POST /api/restify/leads/{lead}/actions?action=generate-message-for-lead',
            schema: ['campaign_id' => ['type' => 'string']],
            examplePayload: ['campaign_id' => '01k…'],
        );

        $this->assertSame([
            'tool_name' => 'leads-generate-message-for-lead-action-tool',
            'repository_uri_key' => 'leads',
            'action_uri_key' => 'generate-message-for-lead',
            'description' => 'Generate a personalized message.',
            'route' => 'POST /api/restify/leads/{lead}/actions?action=generate-message-for-lead',
            'schema' => ['campaign_id' => ['type' => 'string']],
            'example_payload' => ['campaign_id' => '01k…'],
        ], $descriptor->toArray());
    }
}
