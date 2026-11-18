<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;

/**
 * ScopedPostRepository with its actions and getters exposed as MCP tools.
 */
class McpScopedPostRepository extends ScopedPostRepository
{
    use HasMcpTools;

    public static string $uriKey = 'mcp-scoped-posts';

    public function mcpAllowsActions(): bool
    {
        return true;
    }

    public function mcpAllowsGetters(): bool
    {
        return true;
    }
}
