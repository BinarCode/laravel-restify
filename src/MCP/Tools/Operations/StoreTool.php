<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class StoreTool extends Tool
{
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-store-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repository->uriKey();
        $modelName = class_basename($this->repository::guessModelClassName());

        return "Create a new {$modelName} record in the {$uriKey} repository with the provided data.";
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $repositoryClass = get_class($this->repository);
        $repositoryClass::storeToolSchema($schema);

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $result = $this->repository->storeTool($arguments);

        return ToolResult::json($result);
    }
}
