<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteTool extends Tool
{
    /**
     * @var Repository|HasMcpTools
     */
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-delete-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repository->uriKey();
        $modelName = class_basename($this->repository::guessModelClassName());

        return "Delete an existing {$modelName} record by ID from the {$uriKey} repository.";
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);
        $modelName = class_basename($repositoryClass::guessModelClassName());

        return [
            'id' => $schema->string()->description("The ID of the $modelName to delete")->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpDestroyRequest::class);
        $mcpRequest->merge($request->all());

        $result = $this->repository->deleteTool($mcpRequest);

        return Response::json($result);
    }
}
