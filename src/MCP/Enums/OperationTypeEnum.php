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

enum OperationTypeEnum
{
    case index;
    case show;
    case store;
    case update;
    case delete;
    case profile;
    case action;
    case getter;
    case custom;
    case wrapper;

    public static function fromTool(Tool $tool): self
    {
        return match (true) {
            $tool instanceof IndexTool => self::index,
            $tool instanceof ShowTool => self::show,
            $tool instanceof StoreTool => self::store,
            $tool instanceof UpdateTool => self::update,
            $tool instanceof DeleteTool => self::delete,
            $tool instanceof ProfileTool => self::profile,
            $tool instanceof ActionTool => self::action,
            $tool instanceof GetterTool => self::getter,
            $tool instanceof GetOperationDetailsTool,
            $tool instanceof ExecuteOperationTool,
            $tool instanceof DiscoverRepositoriesTool,
            $tool instanceof GetRepositoryOperationsTool => self::wrapper,
            default => self::custom,
        };
    }
}
