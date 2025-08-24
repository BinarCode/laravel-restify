<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('GetMyProfile')]
class ProfileTool extends Tool
{
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-profile-tool";
    }

    public function description(): string
    {
        $modelName = class_basename($this->repository::$model);

        return "Get the current authenticated user profile including {$modelName} and relationship information.";
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $relatedOptions = $this->repository::collectRelated()
            ->intoAssoc()
            ->keys()
            ->toArray();

        $schema->string('include')
            ->description('Comma-separated list of relationships to include in the response. Available options: '.implode(', ', $relatedOptions).' (e.g., include=employee,roles.permissions)');

        return $schema;
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $user = auth()->user();

        if (! $user) {
            return ToolResult::json([
                'error' => 'No authenticated user found',
            ]);
        }

        $arguments['id'] = $user->id;

        $this->repository->request = app(McpRequest::class);

        $result = $this->repository->indexTool($arguments, app(McpRequest::class));

        return ToolResult::json($result);
    }
}
