<?php

namespace Binaryk\LaravelRestify\MCP\Concerns;

/**
 * @mixin \Binaryk\LaravelRestify\Repositories\Repository
 */
trait McpTools
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
        return false;
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
        return true;
    }

    public function mcpAllowsGetters(): bool
    {
        return true;
    }
}
