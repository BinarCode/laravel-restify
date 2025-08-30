<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class IndexTool extends Tool
{
    /**
     * @var Repository|HasMcpTools $repository
     */
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
        $modelName = class_basename($this->repository::guessModelClassName());

        return "Retrieve a paginated list of {$modelName} records from the {$uriKey} repository with filtering, sorting, and search capabilities.";
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = get_class($this->repository);
        $repositoryClass::indexToolSchema($schema);

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $result = $this->repository->indexTool($arguments, app(McpIndexRequest::class));

        return ToolResult::json($result);
    }
}
