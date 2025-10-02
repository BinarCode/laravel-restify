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
        $repositoryClass = get_class($this->repository);
        $modelName = class_basename($repositoryClass::guessModelClassName());
        $actionName = $this->action->name();

        $fields = [];

        if ($this->action->isStandalone()) {
            // Standalone actions don't need ID or repositories
            $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');
        } else {
            // Check if it's primarily a show action or index action
            $mcpRequest = app(McpActionRequest::class);
            $shownOnShow = $this->action->isShownOnShow($mcpRequest, $this->repository);
            $shownOnIndex = $this->action->isShownOnIndex($mcpRequest, $this->repository);

            if ($shownOnShow && ! $shownOnIndex) {
                // Show action - requires single ID
                $fields['id'] = $schema->string()->description("The ID of the $modelName to perform the action on")->required();
                $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');
            } else {
                // Index action - requires repositories array
                $fields['repositories'] = $schema->string()->description("Array of $modelName IDs to perform the action on. e.g. repositories=[1,2,3]")->required();
                $fields['include'] = $schema->string()->description('Comma-separated list of relationships to include');
            }
        }

        return $fields;
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
                return new class($id) {
                    public function __construct(private $id) {}
                    public function parameter($key, $default = null) {
                        return $key === 'repositoryId' ? $this->id : $default;
                    }
                };
            });
        }

        $result = $this->repository->actionTool($this->action, $mcpRequest);

        return Response::json($result);
    }
}
