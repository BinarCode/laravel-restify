<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class StoreTool extends Tool
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
        return $this->repository::label().' Create';
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

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);

        // Use repository's schema method if it has MCP tools
        if (method_exists($repositoryClass, 'storeToolSchema')) {
            $fields = $repositoryClass::storeToolSchema($schema);
        } else {
            $fields = [];
        }

        // Add basic include field
        $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');

        return $fields;
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpStoreRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository->storeTool($mcpRequest);

        return Response::json($result);
    }
}
