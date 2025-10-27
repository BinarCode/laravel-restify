<?php

namespace Binaryk\LaravelRestify\MCP;

use Binaryk\LaravelRestify\MCP\Bootstrap\BootMcpTools;
use Binaryk\LaravelRestify\MCP\Collections\ToolsCollection;
use Binaryk\LaravelRestify\MCP\Enums\OperationTypeEnum;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Manager class for MCP tools discovery and management.
 * This is the real implementation behind the McpTools facade.
 */
class McpToolsManager
{
    /**
     * Discovered tools storage.
     *
     * @var array<string, array{
     *     type: string,
     *     name: string,
     *     title: string,
     *     description: string,
     *     class: string,
     *     instance: \Laravel\Mcp\Server\Tool,
     *     repository?: string,
     *     category: string,
     *     action?: \Binaryk\LaravelRestify\Actions\Action,
     *     getter?: \Binaryk\LaravelRestify\Getters\Getter
     * }>
     */
    protected array $discoveredTools = [];

    /**
     * The MCP server instance.
     */
    protected ?RestifyServer $server = null;

    /**
     * Whether tools have been discovered.
     */
    protected bool $discovered = false;

    public function __construct(
        protected BootMcpTools $bootstrap
    ) {}

    /**
     * Get all discovered tools.
     * Automatically discovers tools if not already done.
     */
    public function all(): ToolsCollection
    {
        if (! $this->discovered) {
            $this->discover();
        }

        return ToolsCollection::make($this->discoveredTools);
    }

    /**
     * Discover all tools from all sources.
     */
    protected function discover(): void
    {
        $tools = $this->bootstrap->boot();
        $this->register($tools);
        $this->discovered = true;
    }

    /**
     * Register discovered tools.
     *
     * @param  array<int, array>  $tools
     */
    public function register(array $tools): void
    {
        foreach ($tools as $tool) {
            $this->discoveredTools[$tool['name']] = $tool;
        }
    }

    /**
     * Get tools for a specific category.
     */
    public function category(string $category): ToolsCollection
    {
        return $this->all()->where('category', $category);
    }

    /**
     * Get tools for a specific repository.
     */
    public function repository(string $repositoryKey): ToolsCollection
    {
        return $this->all()->where('repository', $repositoryKey);
    }

    /**
     * Find a tool by name.
     */
    public function find(string $name): ?array
    {
        return $this->all()->firstWhere('name', $name);
    }

    /**
     * Check if user can use a tool (delegates to server's canUseTool method).
     */
    public function canUse(string|object $tool): bool
    {
        return $this->server()?->canUseTool($tool) ?? true;
    }

    /**
     * Get authorized tools for current user (filtered by permissions).
     */
    public function authorized(): ToolsCollection
    {
        return $this->all()->filter(fn (array $tool): bool => $this->canUse($tool['instance']));
    }

    /**
     * Set the server instance.
     */
    public function setServer(RestifyServer $server): void
    {
        $this->server = $server;
    }

    /**
     * Get the server instance.
     */
    public function server(): ?RestifyServer
    {
        return $this->server;
    }

    /**
     * Clear all discovered tools and cache.
     */
    public function clear(): void
    {
        $this->discoveredTools = [];
        $this->discovered = false;
        $this->server = null;

        // Clear cache for both modes
        Cache::forget('restify.mcp.all_tools_metadata.direct');
        Cache::forget('restify.mcp.all_tools_metadata.wrapper');
        Cache::forget('restify.mcp.all_tools_metadata'); // Legacy key
    }

    /**
     * Get tools grouped by category.
     */
    public function byCategory(): ToolsCollection
    {
        return $this->all()->groupBy('category');
    }

    /**
     * Get tools grouped by repository.
     */
    public function byRepository(): ToolsCollection
    {
        return $this->all()
            ->filter(fn (array $tool): bool => isset($tool['repository']))
            ->groupBy('repository');
    }

    /**
     * Force rediscovery of tools (useful for testing).
     */
    public function rediscover(): void
    {
        $this->clear();
        $this->discover();
    }

    /**
     * Get all available repositories with their MCP-enabled operations.
     */
    public function getAvailableRepositories(?string $search = null): ToolsCollection
    {
        // Get all repository tools from authorized tools
        $repositories = $this->authorized()
            ->filter(fn (array $tool): bool => isset($tool['repository']))
            ->groupBy('repository')
            ->map(function (Collection $tools, string $repositoryKey): array {
                $firstTool = $tools->first();

                // Count operations by type
                $operations = $tools->whereIn('type', [
                    OperationTypeEnum::index,
                    OperationTypeEnum::show,
                    OperationTypeEnum::store,
                    OperationTypeEnum::update,
                    OperationTypeEnum::delete,
                    OperationTypeEnum::profile,
                ])->pluck('type')->map(fn ($type) => $type->name)->values()->toArray();
                $actionsCount = $tools->where('type', OperationTypeEnum::action)->count();
                $gettersCount = $tools->where('type', OperationTypeEnum::getter)->count();

                // Get repository metadata from the first tool instance
                /**
                 * @var Repository $repository
                 */
                $repository = app($firstTool['instance']->repository ?? Repository::class);

                return [
                    'name' => $repositoryKey,
                    'label' => $firstTool['title'] ?? $repositoryKey,
                    'description' => $firstTool['description'] ?? '',
                    'model' => class_basename($repository::guessModelClassName()),
                    'operations' => $operations,
                    'actions_count' => $actionsCount,
                    'getters_count' => $gettersCount,
                ];
            })
            ->values();

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
        // Get all tools for this repository
        $tools = $this->repository($repositoryKey);

        if ($tools->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Filter by permissions
        $tools = $tools->filter(fn (array $tool): bool => $this->canUse($tool['instance']));

        if ($tools->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' found, but you don't have permission to access any of its operations");
        }

        $firstTool = $tools->first();

        // Build operations list
        $operations = $tools->whereIn('type', [
            OperationTypeEnum::index,
            OperationTypeEnum::show,
            OperationTypeEnum::store,
            OperationTypeEnum::update,
            OperationTypeEnum::delete,
            OperationTypeEnum::profile,
        ])
            ->map(fn (array $tool): array => [
                'type' => $tool['type']->name,
                'name' => $tool['name'],
                'title' => $tool['title'],
                'description' => $tool['description'],
            ])
            ->values()
            ->toArray();

        // Build actions list
        $actions = $tools->where('type', OperationTypeEnum::action)
            ->map(fn (array $tool): array => [
                'type' => OperationTypeEnum::action->name,
                'name' => $tool['action']->uriKey(),
                'tool_name' => $tool['name'],
                'title' => $tool['title'],
                'description' => $tool['description'],
            ])
            ->values()
            ->toArray();

        // Build getters list
        $getters = $tools->where('type', OperationTypeEnum::getter)
            ->map(fn (array $tool): array => [
                'type' => OperationTypeEnum::getter->name,
                'name' => $tool['getter']->uriKey(),
                'tool_name' => $tool['name'],
                'title' => $tool['title'],
                'description' => $tool['description'],
            ])
            ->values()
            ->toArray();

        return [
            'repository' => $repositoryKey,
            'label' => $firstTool['title'],
            'description' => $firstTool['description'],
            'operations' => $operations,
            'actions' => $actions,
            'getters' => $getters,
        ];
    }

    /**
     * Get detailed information about a specific operation.
     */
    public function getOperationDetails(string $repositoryKey, string $operationType, ?string $operationName = null): array
    {
        // Convert string operation type to enum
        $operationTypeEnum = OperationTypeEnum::{$operationType};

        // Check if repository has MCP tools
        if ($this->repository($repositoryKey)->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Find the tool
        $tool = $this->repository($repositoryKey)
            ->where('type', $operationTypeEnum)
            ->when($operationName && in_array($operationType, [OperationTypeEnum::action, OperationTypeEnum::getter]), function ($collection) use ($operationName) {
                return $collection->filter(function ($tool) use ($operationName) {
                    if ($tool['type'] === OperationTypeEnum::action) {
                        return $tool['action']->uriKey() === $operationName;
                    }
                    if ($tool['type'] === OperationTypeEnum::getter) {
                        return $tool['getter']->uriKey() === $operationName;
                    }

                    return false;
                });
            })
            ->first();

        if (! $tool) {
            throw new \InvalidArgumentException("Operation '{$operationType}' not found for repository '{$repositoryKey}'");
        }

        // Check permission
        if (! $this->canUse($tool['instance'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to access this operation');
        }

        // Get schema from the tool instance
        $schema = new JsonSchemaTypeFactory;
        $toolSchema = $tool['instance']->schema($schema);

        return [
            'operation' => $tool['name'],
            'type' => $tool['type']->name,
            'title' => $tool['title'],
            'description' => $tool['description'],
            'schema' => $toolSchema,
        ];
    }

    /**
     * Execute an operation with the provided parameters.
     */
    public function executeOperation(string $repositoryKey, string $operationType, ?string $operationName, array $parameters): Response
    {
        // Convert string operation type to enum
        $operationTypeEnum = OperationTypeEnum::{$operationType};

        // Check if repository has MCP tools
        if ($this->repository($repositoryKey)->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Find the tool
        $tool = $this->repository($repositoryKey)
            ->where('type', $operationTypeEnum)
            ->when($operationName && in_array($operationType, [OperationTypeEnum::action, OperationTypeEnum::getter]), function ($collection) use ($operationName) {
                return $collection->filter(function ($tool) use ($operationName) {
                    if ($tool['type'] === OperationTypeEnum::action) {
                        return $tool['action']->uriKey() === $operationName;
                    }
                    if ($tool['type'] === OperationTypeEnum::getter) {
                        return $tool['getter']->uriKey() === $operationName;
                    }

                    return false;
                });
            })
            ->first();

        if (! $tool) {
            throw new \InvalidArgumentException("Operation '{$operationType}' not found for repository '{$repositoryKey}'");
        }

        // Check permission before executing
        if (! $this->canUse($tool['instance'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to execute this operation');
        }

        // Execute based on operation type
        return match ($operationTypeEnum) {
            OperationTypeEnum::index => $this->executeIndexOperation($tool, $parameters),
            OperationTypeEnum::show => $this->executeShowOperation($tool, $parameters),
            OperationTypeEnum::store => $this->executeStoreOperation($tool, $parameters),
            OperationTypeEnum::update => $this->executeUpdateOperation($tool, $parameters),
            OperationTypeEnum::delete => $this->executeDeleteOperation($tool, $parameters),
            OperationTypeEnum::profile => $this->executeProfileOperation($tool, $parameters),
            OperationTypeEnum::action => $this->executeActionOperation($tool, $parameters),
            OperationTypeEnum::getter => $this->executeGetterOperation($tool, $parameters),
            default => throw new \InvalidArgumentException("Invalid operation type: {$operationType}"),
        };
    }

    protected function executeIndexOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeShowOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeStoreOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeUpdateOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeDeleteOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeProfileOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeActionOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }

    protected function executeGetterOperation(array $tool, array $parameters): Response
    {
        $request = new Request($parameters);

        return $tool['instance']->handle($request);
    }
}
