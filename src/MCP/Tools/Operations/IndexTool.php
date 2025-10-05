<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class IndexTool extends Tool
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
        return $this->repository::label() . ' Index';
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-index-tool";
    }

    public function description(): string
    {
        return $this->repository::description(app(McpIndexRequest::class));
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);

        // Use repository's schema method if it has MCP tools
        if (method_exists($repositoryClass, 'indexToolSchema')) {
            return $repositoryClass::indexToolSchema($schema);
        }

        // Fallback to basic schema
        return [
            'page' => $schema->number()->description('Page number for pagination'),
            'perPage' => $schema->number()->description('Number of records per page'),
            'include' => $schema->string()->description('Comma-separated list of relationships to include'),
        ];
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpIndexRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository->indexTool($mcpRequest);

        return Response::json($result);
    }
}
