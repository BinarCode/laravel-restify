<?php

namespace Binaryk\LaravelRestify\MCP;

use Binaryk\LaravelRestify\MCP\Bootstrap\BootMcpTools;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
    public function all(): Collection
    {
        if (! $this->discovered) {
            $this->discover();
        }

        return collect($this->discoveredTools);
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
    public function category(string $category): Collection
    {
        return $this->all()->where('category', $category);
    }

    /**
     * Get tools for a specific repository.
     */
    public function repository(string $repositoryKey): Collection
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
    public function authorized(): Collection
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
    public function byCategory(): Collection
    {
        return $this->all()->groupBy('category');
    }

    /**
     * Get tools grouped by repository.
     */
    public function byRepository(): Collection
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
}
