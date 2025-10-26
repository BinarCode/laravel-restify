<?php

namespace Binaryk\LaravelRestify\MCP\Services;

use Binaryk\LaravelRestify\MCP\McpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ToolRegistry
{
    public function __construct(
        protected ?RestifyServer $server = null
    ) {
        $this->server ??= McpTools::server();
    }

    /**
     * Get all available repositories with their MCP-enabled operations.
     */
    public function getAvailableRepositories(?string $search = null): Collection
    {
        // Get all repository tools from McpTools facade
        $repositories = McpTools::authorized()
            ->filter(fn (array $tool): bool => isset($tool['repository']))
            ->groupBy('repository')
            ->map(function (Collection $tools, string $repositoryKey): array {
                $firstTool = $tools->first();

                // Count operations by type
                $operations = $tools->whereIn('type', ['index', 'show', 'store', 'update', 'delete', 'profile'])->pluck('type')->values()->toArray();
                $actionsCount = $tools->where('type', 'action')->count();
                $gettersCount = $tools->where('type', 'getter')->count();

                // Get repository metadata from the first tool instance
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
        // Get all tools for this repository from McpTools
        $tools = McpTools::repository($repositoryKey);

        if ($tools->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Filter by permissions
        $tools = $tools->filter(fn (array $tool): bool => McpTools::canUse($tool['instance']));

        if ($tools->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' found, but you don't have permission to access any of its operations");
        }

        $firstTool = $tools->first();

        // Build operations list
        $operations = $tools->whereIn('type', ['index', 'show', 'store', 'update', 'delete', 'profile'])
            ->map(fn (array $tool): array => [
                'type' => $tool['type'],
                'name' => $tool['name'],
                'title' => $tool['title'],
                'description' => $tool['description'],
            ])
            ->values()
            ->toArray();

        // Build actions list
        $actions = $tools->where('type', 'action')
            ->map(fn (array $tool): array => [
                'type' => 'action',
                'name' => $tool['action']->uriKey(),
                'tool_name' => $tool['name'],
                'title' => $tool['title'],
                'description' => $tool['description'],
            ])
            ->values()
            ->toArray();

        // Build getters list
        $getters = $tools->where('type', 'getter')
            ->map(fn (array $tool): array => [
                'type' => 'getter',
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
        // Check if repository has MCP tools
        if (McpTools::repository($repositoryKey)->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Find the tool from McpTools
        $tool = McpTools::repository($repositoryKey)
            ->where('type', $operationType)
            ->when($operationName && in_array($operationType, ['action', 'getter']), function ($collection) use ($operationName) {
                return $collection->filter(function ($tool) use ($operationName) {
                    if ($tool['type'] === 'action') {
                        return $tool['action']->uriKey() === $operationName;
                    }
                    if ($tool['type'] === 'getter') {
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
        if (! McpTools::canUse($tool['instance'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to access this operation');
        }

        // Get schema from the tool instance
        $schema = new JsonSchemaTypeFactory;
        $toolSchema = $tool['instance']->schema($schema);

        return [
            'operation' => $tool['name'],
            'type' => $tool['type'],
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
        // Check if repository has MCP tools
        if (McpTools::repository($repositoryKey)->isEmpty()) {
            throw new \InvalidArgumentException("Repository '{$repositoryKey}' does not have MCP tools enabled. Add the HasMcpTools trait to enable MCP support.");
        }

        // Find the tool from McpTools
        $tool = McpTools::repository($repositoryKey)
            ->where('type', $operationType)
            ->when($operationName && in_array($operationType, ['action', 'getter']), function ($collection) use ($operationName) {
                return $collection->filter(function ($tool) use ($operationName) {
                    if ($tool['type'] === 'action') {
                        return $tool['action']->uriKey() === $operationName;
                    }
                    if ($tool['type'] === 'getter') {
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
        if (! McpTools::canUse($tool['instance'])) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to execute this operation');
        }

        // Execute based on operation type
        return match ($operationType) {
            'index' => $this->executeIndexOperation($tool, $parameters),
            'show' => $this->executeShowOperation($tool, $parameters),
            'store' => $this->executeStoreOperation($tool, $parameters),
            'update' => $this->executeUpdateOperation($tool, $parameters),
            'delete' => $this->executeDeleteOperation($tool, $parameters),
            'profile' => $this->executeProfileOperation($tool, $parameters),
            'action' => $this->executeActionOperation($tool, $parameters),
            'getter' => $this->executeGetterOperation($tool, $parameters),
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
