<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ShowTool extends Tool
{
    /**
     * @var Repository|HasMcpTools
     */
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function title(): string
    {
        return $this->repository::label().' Show';
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-show-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repository->uriKey();
        $modelName = class_basename($this->repository::guessModelClassName());

        return "Retrieve a single {$modelName} record by ID from the {$uriKey} repository with optional relationship loading.";
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);

        // Use repository's schema method if it has MCP tools
        if (method_exists($repositoryClass, 'showToolSchema')) {
            return $repositoryClass::showToolSchema($schema);
        }

        // Fallback to basic schema
        $modelName = class_basename($repositoryClass::guessModelClassName());

        return [
            'id' => $schema->string()->description("The ID of the $modelName to retrieve")->required(),
            'include' => $schema->string()->description('Comma-separated list of relationships to include'),
        ];
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpShowRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository->showTool($mcpRequest);

        return Response::json($result);
    }
}
