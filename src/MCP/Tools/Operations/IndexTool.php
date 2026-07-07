<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class IndexTool extends Tool
{
    public function __construct(protected string $repositoryClass) {}

    /**
     * @return Repository|HasMcpTools
     */
    public function repository(): Repository
    {
        return app($this->repositoryClass);
    }

    public function title(): string
    {
        return $this->repositoryClass::label().' Index';
    }

    public function name(): string
    {
        $uriKey = $this->repositoryClass::uriKey();

        return "{$uriKey}-index-tool";
    }

    public function description(): string
    {
        return $this->repositoryClass::description(app(McpIndexRequest::class));
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = $this->repositoryClass;

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

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpIndexRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository()->indexTool($mcpRequest);

        return Response::structured($result);
    }
}
