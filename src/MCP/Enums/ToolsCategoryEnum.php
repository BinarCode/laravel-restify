<?php

namespace Binaryk\LaravelRestify\MCP\Enums;

use Binaryk\LaravelRestify\MCP\Tools\Operations\ActionTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\DeleteTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\GetterTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ProfileTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ShowTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\UpdateTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\DiscoverRepositoriesTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\ExecuteOperationTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetOperationDetailsTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetRepositoryOperationsTool;
use Laravel\Mcp\Server\Tool;

enum ToolsCategoryEnum: string
{
    case CUSTOM_TOOLS = 'Custom Tools';
    case WRAPPER_TOOLS = 'Wrapper Tools';
    case CRUD_OPERATIONS = 'CRUD Operations';
    case ACTIONS = 'Actions';
    case GETTERS = 'Getters';
    case PROFILE = 'Profile';

    public static function fromTool(Tool $tool): self
    {
        return match (true) {
            $tool instanceof IndexTool,
            $tool instanceof ShowTool,
            $tool instanceof StoreTool,
            $tool instanceof UpdateTool,
            $tool instanceof DeleteTool => self::CRUD_OPERATIONS,
            $tool instanceof ProfileTool => self::PROFILE,
            $tool instanceof ActionTool => self::ACTIONS,
            $tool instanceof GetterTool => self::GETTERS,
            $tool instanceof GetOperationDetailsTool,
            $tool instanceof ExecuteOperationTool,
            $tool instanceof DiscoverRepositoriesTool,
            $tool instanceof GetRepositoryOperationsTool => self::WRAPPER_TOOLS,
            default => self::CUSTOM_TOOLS,
        };
    }
}
