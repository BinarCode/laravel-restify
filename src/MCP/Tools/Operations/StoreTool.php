<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class StoreTool extends Tool
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
        return $this->repositoryClass::label().' Create';
    }

    public function name(): string
    {
        $uriKey = $this->repositoryClass::uriKey();

        return "{$uriKey}-store-tool";
    }

    public function description(): string
    {
        return $this->repositoryClass::description(app(McpStoreRequest::class));
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = $this->repositoryClass;

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

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpStoreRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository()->storeTool($mcpRequest);

        return Response::structured($result);
    }
}
