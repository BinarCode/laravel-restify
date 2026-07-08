<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ShowTool extends Tool
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
        return $this->repositoryClass::label().' Show';
    }

    public function name(): string
    {
        $uriKey = $this->repositoryClass::uriKey();

        return "{$uriKey}-show-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repositoryClass::uriKey();
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        return "Retrieve a single {$modelName} record by ID from the {$uriKey} repository with optional relationship loading.";
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = $this->repositoryClass;

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

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpShowRequest::class);
        $mcpRequest->replace($request->all());

        $result = $this->repository()->showTool($mcpRequest);

        return Response::structured($result);
    }
}
