<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ActionTool extends Tool
{
    /**
     * @var Repository|HasMcpTools
     */
    protected Repository $repository;

    protected Action $action;

    public function __construct(string $repositoryClass, Action $action)
    {
        $this->repository = app($repositoryClass);
        $this->action = $action;
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repository->uriKey();
        $actionUriKey = $this->action->uriKey();

        return "{$repositoryUriKey}-{$actionUriKey}-action-tool";
    }

    public function description(): string
    {
        if ($description = $this->action::description(app(McpActionRequest::class))) {
            return $description;
        }

        $repositoryUriKey = $this->repository->uriKey();
        $actionName = $this->action->name();

        $modelName = class_basename($this->repository::guessModelClassName());

        if ($this->action->isStandalone()) {
            return "Execute {$actionName} action (standalone - no models required) in the {$repositoryUriKey} repository.";
        }

        // Check if it's primarily a show action or index action
        $mcpRequest = app(McpActionRequest::class);

        $shownOnShow = $this->action->isShownOnShow($mcpRequest, $this->repository);
        $shownOnIndex = $this->action->isShownOnIndex($mcpRequest, $this->repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$actionName} action on a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$actionName} action on {$modelName} records in the {$repositoryUriKey} repository.";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->action->toolSchema($schema);
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpActionRequest::class);
        $mcpRequest->replace($request->all());
        $mcpRequest->merge([
            'mcp_repository_key' => $this->repository->uriKey(),
        ]);

        // Parse repositories string to array if provided
        if ($mcpRequest->has('repositories') && is_string($mcpRequest->input('repositories'))) {
            $repositories = json_decode($mcpRequest->input('repositories'), true) ?? [];
            $mcpRequest->merge(['repositories' => $repositories]);
        }

        // For show actions with single ID, set the route parameter
        if ($id = $mcpRequest->input('id')) {
            $mcpRequest->setRouteResolver(function () use ($id) {
                return new class($id)
                {
                    public function __construct(private $id) {}

                    public function parameter($key, $default = null)
                    {
                        return $key === 'repositoryId' ? $this->id : $default;
                    }
                };
            });
        }

        $result = $this->repository->actionTool($this->action, $mcpRequest);

        return Response::json($result);
    }
}
