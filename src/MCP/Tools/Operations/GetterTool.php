<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetterTool extends Tool
{
    /**
     * @var Repository|\Illuminate\Foundation\Application|mixed|object|string
     */
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
        if ($description = $this->getter->description(app(McpGetterRequest::class))) {
            return $description;
        }

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
        $validationSchema = [];

        $modelName = class_basename($this->repository::guessModelClassName());

        if ($this->getter->isShownOnIndex(app(RestifyRequest::class), $this->repository)) {
            $validationSchema['resources'] = $schema->array()
                ->items(
                    $schema->string()
                        ->description("The ID of the resource to perform the getter on.")
                        ->required())
                ->title('resources')
                ->description("The ids of the resources {$modelName} to perform the getter on. Use string 'all' to select all resources.")
                ->required();
        } else {
            if ($this->getter->isShownOnShow(app(RestifyRequest::class), $this->repository)) {
                $validationSchema['id'] = $schema->string()
                    ->title('id')
                    ->description("The ID of the resource ({$modelName}) to perform the getter on.")
                    ->required();
            }
        }

        $querySchema = $this->repository::indexToolSchema($schema);

        $rulesSchema = $this->getter->toolSchema($schema);

        return array_merge($querySchema, $rulesSchema, $validationSchema);
    }

    public function handle(Request $request): Response
    {
        $mcpRequest = app(McpGetterRequest::class);
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
                    public function __construct(private $id)
                    {
                    }

                    public function parameter($key, $default = null)
                    {
                        return $key === 'repositoryId' ? $this->id : $default;
                    }
                };
            });
        }

        $result = $this->repository->getterTool($this->getter, $mcpRequest);

        return Response::json($result);
    }
}
