<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
class ActionTool extends Tool
{
    public function __construct(protected string $repositoryClass, protected Action $action) {}

    /**
     * @return Repository|HasMcpTools
     */
    public function repository(): Repository
    {
        return app($this->repositoryClass);
    }

    public function title(): string
    {
        return $this->action->name();
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repositoryClass::uriKey();
        $actionUriKey = $this->action->uriKey();

        return "{$repositoryUriKey}-{$actionUriKey}-action-tool";
    }

    public function description(): string
    {
        if ($description = $this->action->description(app(McpActionRequest::class))) {
            return $description;
        }

        $repository = $this->repository();
        $repositoryUriKey = $this->repositoryClass::uriKey();
        $actionName = $this->action->name();

        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        if ($this->action->isStandalone()) {
            return "Execute {$actionName} action (standalone - no models required) in the {$repositoryUriKey} repository.";
        }

        // Check if it's primarily a show action or index action
        $mcpRequest = app(McpActionRequest::class);

        $shownOnShow = $this->action->isShownOnShow($mcpRequest, $repository);
        $shownOnIndex = $this->action->isShownOnIndex($mcpRequest, $repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$actionName} action on a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$actionName} action on {$modelName} records in the {$repositoryUriKey} repository.";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        $validationSchema = [];

        $repository = $this->repository();
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        if (! $this->action->isStandalone()) {
            if ($this->action->isShownOnIndex(app(RestifyRequest::class), $repository)) {
                $validationSchema['resources'] = $schema->array()
                    ->items(
                        $schema->string()
                            ->description("The ID of the resource {$modelName} to perform the action on.")
                            ->required())
                    ->title('resources')
                    ->description("The ids of the resources {$modelName} to perform the action on. Use string 'all' to select all resources.")
                    ->required();
            } elseif ($this->action->isShownOnShow(app(RestifyRequest::class), $repository)) {
                $validationSchema['id'] = $schema->string()
                    ->title('id')
                    ->description('The ID of the resource to perform the action on.')
                    ->required();
            }
        }

        $rulesSchema = $this->action->toolSchema($schema);

        return array_merge($rulesSchema, $validationSchema);
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpActionRequest::class);
        $mcpRequest->replace($request->all());
        $mcpRequest->merge([
            'mcp_repository_key' => $this->repositoryClass::uriKey(),
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

        $result = $this->repository()->actionTool($this->action, $mcpRequest);

        return Response::structured($result);
    }
}
