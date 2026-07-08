<?php

namespace Binaryk\LaravelRestify\MCP\Tools\Operations;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetterTool extends Tool
{
    public function __construct(protected string $repositoryClass, protected Getter $getter) {}

    public function repository(): Repository
    {
        return app($this->repositoryClass);
    }

    public function title(): string
    {
        return $this->getter->name();
    }

    public function name(): string
    {
        $repositoryUriKey = $this->repositoryClass::uriKey();
        $getterUriKey = $this->getter->uriKey();

        return "{$repositoryUriKey}-{$getterUriKey}-getter-tool";
    }

    public function description(): string
    {
        if ($description = $this->getter->description(app(McpGetterRequest::class))) {
            return $description;
        }

        $repository = $this->repository();
        $repositoryUriKey = $this->repositoryClass::uriKey();
        $getterName = $this->getter->name();
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        // Check if it's primarily a show getter or index getter
        $mcpRequest = app(McpGetterRequest::class);

        $shownOnShow = $this->getter->isShownOnShow($mcpRequest, $repository);
        $shownOnIndex = $this->getter->isShownOnIndex($mcpRequest, $repository);

        if ($shownOnShow && ! $shownOnIndex) {
            return "Execute {$getterName} getter to retrieve data for a specific {$modelName} record in the {$repositoryUriKey} repository.";
        } else {
            return "Execute {$getterName} getter to retrieve data from the {$repositoryUriKey} repository.";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        $validationSchema = [];

        $repository = $this->repository();
        $modelName = class_basename($this->repositoryClass::guessModelClassName());

        if (! $this->getter->isShownOnIndex(app(RestifyRequest::class), $repository)) {
            if ($this->getter->isShownOnShow(app(RestifyRequest::class), $repository)) {
                $validationSchema['id'] = $schema->string()
                    ->title('id')
                    ->required()
                    ->description("The ID of the resource ({$modelName}) to perform the getter on.");
            }
        }

        $rulesSchema = $this->getter->toolSchema($schema);

        return array_merge($rulesSchema, $validationSchema);
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $mcpRequest = app(McpGetterRequest::class);
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

        $result = $this->repository()->getterTool($this->getter, $mcpRequest);

        return Response::structured($result);
    }
}
