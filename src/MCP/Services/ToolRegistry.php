<?php

namespace Binaryk\LaravelRestify\MCP\Services;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpDestroyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpShowRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpStoreRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpUpdateRequest;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ProfileTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Response;

class ToolRegistry
{
    protected string $cacheKey = 'restify.mcp.registry';

    protected int $cacheTtl = 3600;

    /**
     * Get all available repositories with their MCP-enabled operations.
     */
    public function getAvailableRepositories(?string $search = null): Collection
    {
        $repositories = $this->buildRepositoriesMetadata();

        if ($search) {
            $search = strtolower($search);
            $repositories = $repositories->filter(function ($repo) use ($search) {
                return str_contains(strtolower($repo['name']), $search) ||
                    str_contains(strtolower($repo['label']), $search) ||
                    str_contains(strtolower($repo['description'] ?? ''), $search);
            });
        }

        return $repositories->values();
    }

    /**
     * Get all operations available for a specific repository.
     */
    public function getRepositoryOperations(string $repositoryKey): array
    {
        $repositoryClass = $this->findRepositoryClass($repositoryKey);

        if (! $repositoryClass) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' not found");
        }

        if (! $this->hasRepositoryMcpTools($repositoryClass)) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        $repository = app($repositoryClass);

        return [
            'repository' => $repositoryKey,
            'label' => $repositoryClass::label(),
            'description' => $repositoryClass::description(app(McpRequest::class)),
            'operations' => $this->buildRepositoryOperations($repository, $repositoryClass),
            'actions' => $this->buildRepositoryActions($repository, $repositoryClass),
            'getters' => $this->buildRepositoryGetters($repository, $repositoryClass),
        ];
    }

    /**
     * Get detailed information about a specific operation.
     */
    public function getOperationDetails(string $repositoryKey, string $operationType, ?string $operationName = null): array
    {
        $repositoryClass = $this->findRepositoryClass($repositoryKey);

        if (! $repositoryClass) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' not found");
        }

        if (! $this->hasRepositoryMcpTools($repositoryClass)) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        $repository = app($repositoryClass);

        return match ($operationType) {
            'index' => $this->getIndexOperationDetails($repository, $repositoryClass),
            'show' => $this->getShowOperationDetails($repository, $repositoryClass),
            'store' => $this->getStoreOperationDetails($repository, $repositoryClass),
            'update' => $this->getUpdateOperationDetails($repository, $repositoryClass),
            'delete' => $this->getDeleteOperationDetails($repository, $repositoryClass),
            'profile' => $this->getProfileOperationDetails($repository, $repositoryClass),
            'action' => $this->getActionOperationDetails($repository, $repositoryClass, $operationName),
            'getter' => $this->getGetterOperationDetails($repository, $repositoryClass, $operationName),
            default => throw new \InvalidArgumentException("Invalid operation type: {$operationType}"),
        };
    }

    /**
     * Execute an operation with the provided parameters.
     */
    public function executeOperation(string $repositoryKey, string $operationType, ?string $operationName, array $parameters): Response
    {
        $repositoryClass = $this->findRepositoryClass($repositoryKey);

        if (! $repositoryClass) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' not found");
        }

        if (! $this->hasRepositoryMcpTools($repositoryClass)) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        $repository = app($repositoryClass);

        return match ($operationType) {
            'index' => $this->executeIndexOperation($repository, $parameters),
            'show' => $this->executeShowOperation($repository, $parameters),
            'store' => $this->executeStoreOperation($repository, $parameters),
            'update' => $this->executeUpdateOperation($repository, $parameters),
            'delete' => $this->executeDeleteOperation($repository, $parameters),
            'profile' => $this->executeProfileOperation($repository, $parameters),
            'action' => $this->executeActionOperation($repository, $repositoryClass, $operationName, $parameters),
            'getter' => $this->executeGetterOperation($repository, $repositoryClass, $operationName, $parameters),
            default => throw new \InvalidArgumentException("Invalid operation type: {$operationType}"),
        };
    }

    /**
     * Build metadata for all repositories that have MCP tools enabled.
     */
    protected function buildRepositoriesMetadata(): Collection
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return collect(Restify::$repositories)
                ->filter(fn ($repoClass) => $this->hasRepositoryMcpTools($repoClass))
                ->map(function ($repositoryClass) {
                    $repository = app($repositoryClass);
                    $operations = [];

                    if ($repository::uriKey() === 'users') {
                        $operations[] = 'profile';
                    }

                    if (method_exists($repository, 'mcpAllowsIndex') && $repository->mcpAllowsIndex()) {
                        $operations[] = 'index';
                    }

                    if (method_exists($repository, 'mcpAllowsShow') && $repository->mcpAllowsShow()) {
                        $operations[] = 'show';
                    }

                    if (method_exists($repository, 'mcpAllowsStore') && $repository->mcpAllowsStore()) {
                        $operations[] = 'store';
                    }

                    if (method_exists($repository, 'mcpAllowsUpdate') && $repository->mcpAllowsUpdate()) {
                        $operations[] = 'update';
                    }

                    if (method_exists($repository, 'mcpAllowsDelete') && $repository->mcpAllowsDelete()) {
                        $operations[] = 'delete';
                    }

                    $actionsCount = 0;
                    $gettersCount = 0;

                    if (method_exists($repository, 'mcpAllowsActions') && $repository->mcpAllowsActions()) {
                        $actionsCount = $this->countRepositoryActions($repository, $repositoryClass);
                    }

                    if (method_exists($repository, 'mcpAllowsGetters') && $repository->mcpAllowsGetters()) {
                        $gettersCount = $this->countRepositoryGetters($repository, $repositoryClass);
                    }

                    return [
                        'name' => $repository::uriKey(),
                        'label' => $repositoryClass::label(),
                        'description' => $repositoryClass::description(app(McpRequest::class)),
                        'model' => class_basename($repositoryClass::guessModelClassName()),
                        'operations' => $operations,
                        'actions_count' => $actionsCount,
                        'getters_count' => $gettersCount,
                    ];
                })
                ->values();
        });
    }

    protected function hasRepositoryMcpTools(string $repositoryClass): bool
    {
        return in_array(HasMcpTools::class, class_uses_recursive($repositoryClass));
    }

    protected function findRepositoryClass(string $repositoryKey): ?string
    {
        return collect(Restify::$repositories)
            ->first(fn ($repoClass) => app($repoClass)::uriKey() === $repositoryKey);
    }

    protected function buildRepositoryOperations(Repository $repository, string $repositoryClass): array
    {
        $operations = [];

        if ($repository::uriKey() === 'users') {
            $operations[] = [
                'type' => 'profile',
                'name' => 'profile-tool',
                'title' => 'Profile',
                'description' => 'Get the authenticated user profile',
            ];
        }

        if (method_exists($repository, 'mcpAllowsIndex') && $repository->mcpAllowsIndex()) {
            $operations[] = [
                'type' => 'index',
                'name' => "{$repository::uriKey()}-index-tool",
                'title' => "{$repositoryClass::label()} Index",
                'description' => $repositoryClass::description(app(McpIndexRequest::class)),
            ];
        }

        if (method_exists($repository, 'mcpAllowsShow') && $repository->mcpAllowsShow()) {
            $operations[] = [
                'type' => 'show',
                'name' => "{$repository::uriKey()}-show-tool",
                'title' => "{$repositoryClass::label()} Show",
                'description' => "Show a specific {$repositoryClass::label()} record",
            ];
        }

        if (method_exists($repository, 'mcpAllowsStore') && $repository->mcpAllowsStore()) {
            $operations[] = [
                'type' => 'store',
                'name' => "{$repository::uriKey()}-store-tool",
                'title' => "{$repositoryClass::label()} Create",
                'description' => "Create a new {$repositoryClass::label()} record",
            ];
        }

        if (method_exists($repository, 'mcpAllowsUpdate') && $repository->mcpAllowsUpdate()) {
            $operations[] = [
                'type' => 'update',
                'name' => "{$repository::uriKey()}-update-tool",
                'title' => "{$repositoryClass::label()} Update",
                'description' => "Update an existing {$repositoryClass::label()} record",
            ];
        }

        if (method_exists($repository, 'mcpAllowsDelete') && $repository->mcpAllowsDelete()) {
            $operations[] = [
                'type' => 'delete',
                'name' => "{$repository::uriKey()}-delete-tool",
                'title' => "{$repositoryClass::label()} Delete",
                'description' => "Delete a {$repositoryClass::label()} record",
            ];
        }

        return $operations;
    }

    protected function buildRepositoryActions(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsActions') || ! $repository->mcpAllowsActions()) {
            return [];
        }

        $actionRequest = app(McpActionRequest::class);

        return $repository->resolveActions($actionRequest)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn (Action $action) => $action->isShownOnMcp($actionRequest, $repository))
            ->filter(fn (Action $action) => $action->authorizedToSee($actionRequest))
            ->unique(fn (Action $action) => $action->uriKey())
            ->map(fn (Action $action) => [
                'type' => 'action',
                'name' => $action->uriKey(),
                'tool_name' => "{$repository::uriKey()}-{$action->uriKey()}-action-tool",
                'title' => $action->name(),
                'description' => $action->description($actionRequest) ?? "Execute {$action->name()} action",
            ])
            ->values()
            ->toArray();
    }

    protected function buildRepositoryGetters(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsGetters') || ! $repository->mcpAllowsGetters()) {
            return [];
        }

        $getterRequest = app(McpGetterRequest::class);

        return $repository->resolveGetters($getterRequest)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn (Getter $getter) => $getter->isShownOnMcp($getterRequest, $repository))
            ->filter(fn (Getter $getter) => $getter->authorizedToSee($getterRequest))
            ->unique(fn (Getter $getter) => $getter->uriKey())
            ->map(fn (Getter $getter) => [
                'type' => 'getter',
                'name' => $getter->uriKey(),
                'tool_name' => "{$repository::uriKey()}-{$getter->uriKey()}-getter-tool",
                'title' => $getter->name(),
                'description' => $getter->description($getterRequest) ?? "Execute {$getter->name()} getter",
            ])
            ->values()
            ->toArray();
    }

    protected function countRepositoryActions(Repository $repository, string $repositoryClass): int
    {
        $actionRequest = app(McpActionRequest::class);

        return $repository->resolveActions($actionRequest)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn (Action $action) => $action->isShownOnMcp($actionRequest, $repository))
            ->filter(fn (Action $action) => $action->authorizedToSee($actionRequest))
            ->unique(fn (Action $action) => $action->uriKey())
            ->count();
    }

    protected function countRepositoryGetters(Repository $repository, string $repositoryClass): int
    {
        $getterRequest = app(McpGetterRequest::class);

        return $repository->resolveGetters($getterRequest)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn (Getter $getter) => $getter->isShownOnMcp($getterRequest, $repository))
            ->filter(fn (Getter $getter) => $getter->authorizedToSee($getterRequest))
            ->unique(fn (Getter $getter) => $getter->uriKey())
            ->count();
    }

    protected function getIndexOperationDetails(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsIndex') || ! $repository->mcpAllowsIndex()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow index operation");
        }

        $schema = new JsonSchemaTypeFactory;

        if (! method_exists($repositoryClass, 'indexToolSchema')) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not support index operation");
        }

        return [
            'operation' => "{$repository::uriKey()}-index-tool",
            'type' => 'index',
            'title' => "{$repositoryClass::label()} Index",
            'description' => $repositoryClass::description(app(McpIndexRequest::class)),
            'schema' => $repositoryClass::indexToolSchema($schema),
        ];
    }

    protected function getShowOperationDetails(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsShow') || ! $repository->mcpAllowsShow()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow show operation");
        }

        $schema = new JsonSchemaTypeFactory;

        if (! method_exists($repositoryClass, 'showToolSchema')) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not support show operation");
        }

        return [
            'operation' => "{$repository::uriKey()}-show-tool",
            'type' => 'show',
            'title' => "{$repositoryClass::label()} Show",
            'description' => "Show a specific {$repositoryClass::label()} record",
            'schema' => $repositoryClass::showToolSchema($schema),
        ];
    }

    protected function getStoreOperationDetails(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsStore') || ! $repository->mcpAllowsStore()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow store operation");
        }

        $schema = new JsonSchemaTypeFactory;

        if (! method_exists($repositoryClass, 'storeToolSchema')) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not support store operation");
        }

        return [
            'operation' => "{$repository::uriKey()}-store-tool",
            'type' => 'store',
            'title' => "{$repositoryClass::label()} Create",
            'description' => "Create a new {$repositoryClass::label()} record",
            'schema' => $repositoryClass::storeToolSchema($schema),
        ];
    }

    protected function getUpdateOperationDetails(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsUpdate') || ! $repository->mcpAllowsUpdate()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow update operation");
        }

        $schema = new JsonSchemaTypeFactory;

        if (! method_exists($repositoryClass, 'updateToolSchema')) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not support update operation");
        }

        return [
            'operation' => "{$repository::uriKey()}-update-tool",
            'type' => 'update',
            'title' => "{$repositoryClass::label()} Update",
            'description' => "Update an existing {$repositoryClass::label()} record",
            'schema' => $repositoryClass::updateToolSchema($schema),
        ];
    }

    protected function getDeleteOperationDetails(Repository $repository, string $repositoryClass): array
    {
        if (! method_exists($repository, 'mcpAllowsDelete') || ! $repository->mcpAllowsDelete()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow delete operation");
        }

        $schema = new JsonSchemaTypeFactory;

        if (! method_exists($repositoryClass, 'destroyToolSchema')) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not support delete operation");
        }

        return [
            'operation' => "{$repository::uriKey()}-delete-tool",
            'type' => 'delete',
            'title' => "{$repositoryClass::label()} Delete",
            'description' => "Delete a {$repositoryClass::label()} record",
            'schema' => $repositoryClass::destroyToolSchema($schema),
        ];
    }

    protected function getProfileOperationDetails(Repository $repository, string $repositoryClass): array
    {
        $schema = new JsonSchemaTypeFactory;

        return [
            'operation' => 'profile-tool',
            'type' => 'profile',
            'title' => 'Profile',
            'description' => 'Get the authenticated user profile',
            'schema' => [
                'include' => $schema->string()->description('Comma-separated list of relationships to include'),
            ],
        ];
    }

    protected function getActionOperationDetails(Repository $repository, string $repositoryClass, ?string $actionName): array
    {
        if (! $actionName) {
            throw new \InvalidArgumentException('Action name is required for action operation type');
        }

        $actionRequest = app(McpActionRequest::class);
        $action = $repository->resolveActions($actionRequest)
            ->filter(fn ($a) => $a instanceof Action)
            ->firstWhere(fn (Action $a) => $a->uriKey() === $actionName);

        if (! $action) {
            throw new \InvalidArgumentException("Action '{$actionName}' not found in repository '{$repository::uriKey()}'");
        }

        $schema = new JsonSchemaTypeFactory;

        return [
            'operation' => "{$repository::uriKey()}-{$action->uriKey()}-action-tool",
            'type' => 'action',
            'title' => $action->name(),
            'description' => $action->description($actionRequest) ?? "Execute {$action->name()} action",
            'schema' => $repositoryClass::actionToolSchema($action, $schema, $actionRequest),
        ];
    }

    protected function getGetterOperationDetails(Repository $repository, string $repositoryClass, ?string $getterName): array
    {
        if (! $getterName) {
            throw new \InvalidArgumentException('Getter name is required for getter operation type');
        }

        $getterRequest = app(McpGetterRequest::class);
        $getter = $repository->resolveGetters($getterRequest)
            ->filter(fn ($g) => $g instanceof Getter)
            ->firstWhere(fn (Getter $g) => $g->uriKey() === $getterName);

        if (! $getter) {
            throw new \InvalidArgumentException("Getter '{$getterName}' not found in repository '{$repository::uriKey()}'");
        }

        $schema = new JsonSchemaTypeFactory;

        return [
            'operation' => "{$repository::uriKey()}-{$getter->uriKey()}-getter-tool",
            'type' => 'getter',
            'title' => $getter->name(),
            'description' => $getter->description($getterRequest) ?? "Execute {$getter->name()} getter",
            'schema' => $repositoryClass::getterToolSchema($getter, $schema, $getterRequest),
        ];
    }

    protected function executeIndexOperation(Repository $repository, array $parameters): Response
    {
        if (! method_exists($repository, 'mcpAllowsIndex') || ! $repository->mcpAllowsIndex()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow index operation");
        }

        $request = app(McpIndexRequest::class);
        $request->replace($parameters);

        $result = $repository->indexTool($request);

        return Response::json($result);
    }

    protected function executeShowOperation(Repository $repository, array $parameters): Response
    {
        if (! method_exists($repository, 'mcpAllowsShow') || ! $repository->mcpAllowsShow()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow show operation");
        }

        $request = app(McpShowRequest::class);
        $request->replace($parameters);

        if ($id = $request->input('id')) {
            $request->setRouteResolver(function () use ($id) {
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

        $result = $repository->showTool($request);

        return Response::json($result);
    }

    protected function executeStoreOperation(Repository $repository, array $parameters): Response
    {
        if (! method_exists($repository, 'mcpAllowsStore') || ! $repository->mcpAllowsStore()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow store operation");
        }

        $request = app(McpStoreRequest::class);
        $request->replace($parameters);

        $result = $repository->storeTool($request);

        return Response::json($result);
    }

    protected function executeUpdateOperation(Repository $repository, array $parameters): Response
    {
        if (! method_exists($repository, 'mcpAllowsUpdate') || ! $repository->mcpAllowsUpdate()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow update operation");
        }

        $request = app(McpUpdateRequest::class);
        $request->replace($parameters);

        if ($id = $request->input('id')) {
            $request->setRouteResolver(function () use ($id) {
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

        $result = $repository->updateTool($request);

        return Response::json($result);
    }

    protected function executeDeleteOperation(Repository $repository, array $parameters): Response
    {
        if (! method_exists($repository, 'mcpAllowsDelete') || ! $repository->mcpAllowsDelete()) {
            throw new \InvalidArgumentException("Repository '{$repository::uriKey()}' does not allow delete operation");
        }

        $request = app(McpDestroyRequest::class);
        $request->replace($parameters);

        if ($id = $request->input('id')) {
            $request->setRouteResolver(function () use ($id) {
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

        $result = $repository->deleteTool($request);

        return Response::json($result);
    }

    protected function executeProfileOperation(Repository $repository, array $parameters): Response
    {
        $tool = new ProfileTool(get_class($repository));
        $request = app(McpRequest::class);
        $request->replace($parameters);

        return $tool->handle($request);
    }

    protected function executeActionOperation(Repository $repository, string $repositoryClass, ?string $actionName, array $parameters): Response
    {
        if (! $actionName) {
            throw new \InvalidArgumentException('Action name is required for action operation type');
        }

        $actionRequest = app(McpActionRequest::class);
        $action = $repository->resolveActions($actionRequest)
            ->filter(fn ($a) => $a instanceof Action)
            ->firstWhere(fn (Action $a) => $a->uriKey() === $actionName);

        if (! $action) {
            throw new \InvalidArgumentException("Action '{$actionName}' not found in repository '{$repository::uriKey()}'");
        }

        $actionRequest->replace($parameters);
        $actionRequest->merge([
            'mcp_repository_key' => $repository->uriKey(),
        ]);

        if ($actionRequest->has('repositories') && is_string($actionRequest->input('repositories'))) {
            $repositories = json_decode($actionRequest->input('repositories'), true) ?? [];
            $actionRequest->merge(['repositories' => $repositories]);
        }

        if ($id = $actionRequest->input('id')) {
            $actionRequest->setRouteResolver(function () use ($id) {
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

        $result = $repository->actionTool($action, $actionRequest);

        return Response::json($result);
    }

    protected function executeGetterOperation(Repository $repository, string $repositoryClass, ?string $getterName, array $parameters): Response
    {
        if (! $getterName) {
            throw new \InvalidArgumentException('Getter name is required for getter operation type');
        }

        $getterRequest = app(McpGetterRequest::class);
        $getter = $repository->resolveGetters($getterRequest)
            ->filter(fn ($g) => $g instanceof Getter)
            ->firstWhere(fn (Getter $g) => $g->uriKey() === $getterName);

        if (! $getter) {
            throw new \InvalidArgumentException("Getter '{$getterName}' not found in repository '{$repository::uriKey()}'");
        }

        $getterRequest->replace($parameters);
        $getterRequest->merge([
            'mcp_repository_key' => $repository->uriKey(),
        ]);

        if ($getterRequest->has('repositories') && is_string($getterRequest->input('repositories'))) {
            $repositories = json_decode($getterRequest->input('repositories'), true) ?? [];
            $getterRequest->merge(['repositories' => $repositories]);
        }

        if ($id = $getterRequest->input('id')) {
            $getterRequest->setRouteResolver(function () use ($id) {
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

        $result = $repository->getterTool($getter, $getterRequest);

        return Response::json($result);
    }

    /**
     * Clear the registry cache.
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
