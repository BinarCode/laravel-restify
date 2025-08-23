<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class IndexTool extends Tool
{
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-index-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repository->uriKey();
        $modelName = class_basename($this->repository::$model);

        return "Retrieve a paginated list of {$modelName} records from the {$uriKey} repository with filtering, sorting, and search capabilities.";
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = $this->repository;
        $repositoryClass::indexToolSchema($schema);

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $this->repository->request = app(McpRequest::class);

        $result = $this->repository->indexTool($arguments, app(McpRequest::class));

        return ToolResult::json($result);
    }
}
