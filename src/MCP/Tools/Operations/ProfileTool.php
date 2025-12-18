<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProfileTool extends Tool
{
    protected Repository $repository;

    public function __construct(string $repositoryClass)
    {
        $this->repository = app($repositoryClass);
    }

    public function title(): string
    {
        return 'Profile Tool';
    }

    public function name(): string
    {
        $uriKey = $this->repository->uriKey();

        return "{$uriKey}-profile-tool";
    }

    public function description(): string
    {
        $modelName = class_basename($this->repository::guessModelClassName());

        return "Get the current authenticated user profile including {$modelName} and relationship information.";
    }

    public function schema(JsonSchema $schema): array
    {
        $relatedOptions = $this->repository::collectRelated()
            ->intoAssoc()
            ->keys()
            ->toArray();

        return [
            'include' => $schema->string()->description('Comma-separated list of relationships to include in the response. Available options: '.implode(', ',
                $relatedOptions).' (e.g., include=employee,roles.permissions)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = auth()->user();

        if (! $user) {
            return Response::json([
                'error' => 'No authenticated user found',
            ]);
        }

        $mcpRequest = app(McpIndexRequest::class);
        $requestData = $request->all();
        $requestData['id'] = $user->getKey();
        $mcpRequest->replace($requestData);

        $this->repository->request = $mcpRequest;

        $result = $this->repository->indexTool($mcpRequest);

        return Response::json($result);
    }
}
