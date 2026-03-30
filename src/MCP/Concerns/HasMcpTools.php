<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

use Binaryk\LaravelRestify\Repositories\Repository;

/**
 * @mixin Repository
 */
trait HasMcpTools
{
    use McpActionTool;
    use McpDestroyTool;
    use McpGetterTool;
    use McpIndexTool;
    use McpShowTool;
    use McpStoreTool;
    use McpToolHelpers;
    use McpUpdateTool;

    public function mcpAllowsIndex(): bool
    {
        return true;
    }

    public function mcpAllowsShow(): bool
    {
        return false;
    }

    public function mcpAllowsStore(): bool
    {
        return false;
    }

    public function mcpAllowsUpdate(): bool
    {
        return false;
    }

    public function mcpAllowsDelete(): bool
    {
        return false;
    }

    public function mcpAllowsActions(): bool
    {
        return false;
    }

    public function mcpAllowsGetters(): bool
    {
        return false;
    }
}
