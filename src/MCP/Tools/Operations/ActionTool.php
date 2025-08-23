<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class ActionTool extends Tool
{
    protected Repository $repository;

    protected Action $action;

    public function __construct(string $repositoryClass, Action $action)
    {
        $this->repository = app($repositoryClass);
        $this->action = $action;
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $actionUriKey = $this->action->uriKey();

        return "{$repositoryUriKey}-{$actionUriKey}-action-tool";
    }

    public function description(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $actionName = $this->action->name();
        $modelName = class_basename($this->repository::$model);

        if ($this->action->isStandalone()) {
            return "Execute {$actionName} action (standalone - no models required) in the {$repositoryUriKey} repository.";
        }

        // Check if it's primarily a show action or index action
        $mcpRequest = app(McpActionRequest::class);

        $shownOnShow = $this->action->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->action->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$actionName} action on a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$actionName} action on {$modelName} records in the {$repositoryUriKey} repository.";
        }
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = $this->repository;
        $repositoryClass::actionToolSchema($this->action, $schema, app(McpActionRequest::class));

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $this->repository->request = app(McpActionRequest::class);

        $result = $this->repository->actionTool($this->action, $arguments, app(McpActionRequest::class));

        return ToolResult::json($result);
    }
}
