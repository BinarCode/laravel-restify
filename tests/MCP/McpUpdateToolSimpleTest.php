<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class McpUpdateToolSimpleTest extends IntegrationTestCase
{
    use RefreshDatabase;

    public function test_mcp_update_request_calls_fields_for_mcp_update(): void
    {
        $repository = new TestUpdateRepository;

        // Test with regular request - should use fields()
        $regularRequest = new RestifyRequest;
        $regularFields = $repository->collectFields($regularRequest);
        $regularFieldNames = $regularFields->map(fn ($field) => $field->getAttribute())->toArray();

        // Should return only regular fields
        $this->assertCount(2, $regularFields);
        $this->assertContains('title', $regularFieldNames);
        $this->assertContains('description', $regularFieldNames);
        $this->assertNotContains('mcp_update_field', $regularFieldNames);

        // Test with McpUpdateRequest - should use fieldsForMcpUpdate()
        $mcpUpdateRequest = new McpUpdateRequest;
        $this->assertTrue($mcpUpdateRequest->isUpdateRequest());

        $mcpFields = $repository->collectFields($mcpUpdateRequest);
        $mcpFieldNames = $mcpFields->map(fn ($field) => $field->getAttribute())->toArray();

        // Should return MCP-specific fields
        $this->assertCount(4, $mcpFields);
        $this->assertContains('title', $mcpFieldNames);
        $this->assertContains('description', $mcpFieldNames);
        $this->assertContains('mcp_update_field', $mcpFieldNames);
        $this->assertContains('update_metadata', $mcpFieldNames);
    }
}

class TestUpdateRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('description'),
        ];
    }

    public function fieldsForMcpUpdate(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('description'),
            Field::make('mcp_update_field')->description('This field only appears in MCP update requests'),
            Field::make('update_metadata')->description('MCP update metadata'),
        ];
    }

    public function mcpAllowsUpdate(): bool
    {
        return true;
    }
}
