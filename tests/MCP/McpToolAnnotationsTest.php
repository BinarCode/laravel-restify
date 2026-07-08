<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Tools\Operations\DeleteTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\UpdateTool;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class McpToolAnnotationsTest extends IntegrationTestCase
{
    public function test_delete_tool_definition_exposes_destructive_and_idempotent_hints(): void
    {
        $definition = (new DeleteTool(PostRepository::class))->toArray();

        $this->assertArrayHasKey('annotations', $definition);
        $this->assertTrue($definition['annotations']['destructiveHint']);
        $this->assertTrue($definition['annotations']['idempotentHint']);
    }

    public function test_index_tool_definition_exposes_read_only_hint(): void
    {
        $definition = (new IndexTool(PostRepository::class))->toArray();

        $this->assertTrue($definition['annotations']['readOnlyHint']);
    }

    public function test_update_tool_definition_exposes_idempotent_hint(): void
    {
        $definition = (new UpdateTool(PostRepository::class))->toArray();

        $this->assertTrue($definition['annotations']['idempotentHint']);
        $this->assertArrayNotHasKey('destructiveHint', $definition['annotations']);
    }

    public function test_store_tool_definition_has_no_safety_hints(): void
    {
        $annotations = (new StoreTool(PostRepository::class))->annotations();

        $this->assertSame([], $annotations);
    }
}
