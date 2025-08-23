<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class GetterTool extends Tool
{
    protected Repository $repository;
    protected Getter $getter;

    public function __construct(string $repositoryClass, Getter $getter)
    {
        $this->repository = app($repositoryClass);
        $this->getter = $getter;
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $getterUriKey = $this->getter->uriKey();

        return "{$repositoryUriKey}-{$getterUriKey}-getter-tool";
    }

    public function description(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $getterName = $this->getter->name();
        $modelName = class_basename($this->repository::$model);

        // Check if it's primarily a show getter or index getter
        $mcpRequest = app(McpGetterRequest::class);

        $shownOnShow = $this->getter->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->getter->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && !$shownOnIndex) {
            return "Execute {$getterName} getter to retrieve data for a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$getterName} getter to retrieve data from the {$repositoryUriKey} repository.";
        }
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = $this->repository;
        $repositoryClass::getterToolSchema($this->getter, $schema, app(McpGetterRequest::class));

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $this->repository->request = app(McpGetterRequest::class);

        $result = $this->repository->getterTool($this->getter, $arguments, app(McpGetterRequest::class));

        return ToolResult::json($result);
    }
}
