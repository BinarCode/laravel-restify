<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class DeleteTool extends Tool
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
        return $this->repositoryClass::label().' Delete';
    }

    public function name(): string
    {
        $uriKey = $this->repositoryClass::uriKey();

        return "{$uriKey}-delete-tool";
    }

    public function description(): string
    {
        $uriKey = $this->repositoryClass::uriKey();
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        return "Delete an existing {$modelName} record by ID from the {$uriKey} repository.";
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = $this->repositoryClass;
        $modelName = class_basename($repositoryClass::guessModelClassName());

        return [
            'id' => $schema->string()->description("The ID of the $modelName to delete")->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpDestroyRequest::class);
        $mcpRequest->merge($request->all());

        $result = $this->repository()->deleteTool($mcpRequest);

        return Response::structured($result);
    }
}
