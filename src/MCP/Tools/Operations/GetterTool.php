<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetterTool extends Tool
{
    protected Repository $repository;

    protected Getter $getter;

    public function __construct(string $repositoryClass, Getter $getter)
    {
        $this->repository = app($repositoryClass);
        $this->getter = $getter;
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $getterUriKey = $this->getter->uriKey();

        return "{$repositoryUriKey}-{$getterUriKey}-getter-tool";
    }

    public function description(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $getterName = $this->getter->name();
        $modelName = class_basename($this->repository::guessModelClassName());

        // Check if it's primarily a show getter or index getter
        $mcpRequest = app(McpGetterRequest::class);

        $shownOnShow = $this->getter->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->getter->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$getterName} getter to retrieve data for a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$getterName} getter to retrieve data from the {$repositoryUriKey} repository.";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        $repositoryClass = get_class($this->repository);
        $modelName = class_basename($repositoryClass::guessModelClassName());
        $getterName = $this->getter->name();

        $fields = [];

        // Check if it's primarily a show getter or index getter
        $mcpRequest = app(McpGetterRequest::class);
        $shownOnShow = $this->getter->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->getter->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && ! $shownOnIndex) {
            // Show getter - requires single ID
            $fields['id'] = $schema->string()->description("The ID of the $modelName to execute the getter on")->required();
            $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');
        } else {
            // Index getters typically don't require specific IDs
            $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');
        }

        return $fields;
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpGetterRequest::class);
        $mcpRequest->replace($request->all());

        $this->repository->request = $mcpRequest;

        $result = $this->repository->getterTool($this->getter, $mcpRequest);

        return Response::json($result);
    }
}
