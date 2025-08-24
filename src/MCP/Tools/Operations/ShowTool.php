<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class ShowTool extends Tool
{
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-show-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repository->uriKey();
        $modelName = class_basename($this->repository::$model);

        return "Retrieve a single {$modelName} record by ID from the {$uriKey} repository with optional relationship loading.";
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = get_class($this->repository);
        $repositoryClass::showToolSchema($schema);

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $result = $this->repository->showTool($arguments, app(McpRequest::class));

        return ToolResult::json($result);
    }
}
