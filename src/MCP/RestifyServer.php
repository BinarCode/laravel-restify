<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\MCP;

/**
 * PERMISSION SYSTEM USAGE
 * =======================
 *
 * This server provides fine-grained permission control for MCP tools using token-based authorization.
 *
 * ## Token Creation UI
 *
 * To display all available tools in your token creation UI:
 *
 * ```php
 * $server = app(RestifyServer::class);
 * $allTools = $server->getAllAvailableTools();
 *
 * // Returns ALL tools regardless of mode (direct or wrapper):
 * // - In "direct" mode: Repository CRUD operations, actions, getters, custom tools
 * // - In "wrapper" mode: 4 wrapper tools + all repository operations, actions, getters, custom tools
 * //
 * // User can then select which tools to grant access to for this token.
 * ```
 *
 * ## Implementing Permission Checks
 *
 * Override `canUseTool()` in your application's MCP server:
 *
 * ```php
 * // app/Mcp/ApplicationServer.php
 * class ApplicationServer extends \Binaryk\LaravelRestify\MCP\RestifyServer
 * {
 *     public function canUseTool(string|object $tool): bool
 *     {
 *         // Get tool name
 *         $toolName = is_string($tool) ? $tool : $tool->name();
 *
 *         // Get current token from request
 *         $token = request()->bearerToken();
 *         $mcpToken = McpToken::where('token', hash('sha256', $token))->first();
 *
 *         if (!$mcpToken) {
 *             return false;
 *         }
 *
 *         // Check if token has permission for this tool
 *         // $mcpToken->allowed_tools is JSON array like: ["users-index", "posts-store", "posts-update-status-action"]
 *         return in_array($toolName, $mcpToken->allowed_tools ?? [], true);
 *     }
 * }
 * ```
 *
 * ## How Permissions Work in Wrapper Mode
 *
 * Even though wrapper mode only registers 4 wrapper tools with the MCP server,
 * ALL individual operations are still discovered and available for permission checks:
 *
 * 1. AI calls wrapper tool: `execute-operation(repository="posts", operation="store")`
 * 2. Wrapper tool looks up "posts-store" in McpTools
 * 3. Before execution, calls `canUseTool("posts-store")`
 * 4. Your `canUseTool()` implementation checks if token has "posts-store" permission
 * 5. If yes, executes; if no, throws AuthorizationException
 *
 * This allows fine-grained permissions even in wrapper mode!
 *
 * ## Getting Authorized Tools at Runtime
 *
 * To get only tools the current user/token can access:
 *
 * ```php
 * $authorizedTools = $server->getAuthorizedTools();
 * // Returns tools filtered by canUseTool()
 * ```
 */

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\MCP\Bootstrap\BootMcpTools;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpActionRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpGetterRequest;
use Binaryk\LaravelRestify\MCP\Resources\ApplicationInfo;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ActionTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\DeleteTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\GetterTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ProfileTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ShowTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\UpdateTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\DiscoverRepositoriesTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\ExecuteOperationTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetOperationDetailsTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetRepositoryOperationsTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Laravel\Mcp\Server;

class RestifyServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Laravel Restify';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    public string $instructions = 'Laravel Restify MCP server providing access to RESTful API resources, repository operations, field management, action execution, and filter/search capabilities. Restify helps build and interact with REST APIs efficiently.';

    /**
     * The maximum pagination length for resources that support pagination.
     */
    public int $maxPaginationLength = 200;

    /**
     * The default pagination length for resources that support pagination.
     */
    public int $defaultPaginationLength = 150;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        ApplicationInfo::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];

    protected function boot(): void
    {
        // Set this server instance in McpTools facade
        // Tools will be auto-discovered on first access via McpTools::all()
        McpTools::setServer($this);

        // Register tools with the server
        collect($this->discoverTools())->each(fn (string $tool): string => $this->tools[] = $tool);
        $this->discoverRepositoryTools();
        collect($this->discoverResources())->each(fn (string $resource): string => $this->resources[] = $resource);
        collect($this->discoverPrompts())->each(fn (string $prompt): string => $this->prompts[] = $prompt);
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected function discoverTools(): array
    {
        return McpTools::category('Custom Tools')
            ->filter(fn (array $tool): bool => $this->canUseTool($tool['instance']))
            ->pluck('class')
            ->toArray();
    }

    protected function discoverRepositoryTools(): void
    {
        // Check if we should use wrapper mode
        if (config('restify.mcp.mode') === 'wrapper') {
            $this->registerWrapperTools();

            return;
        }

        // Direct mode - register each operation as a separate tool
        McpTools::all()
            ->whereIn('category', ['CRUD Operations', 'Actions', 'Getters', 'Profile'])
            ->filter(fn (array $tool): bool => $this->canUseTool($tool['instance']))
            ->each(fn (array $tool) => $this->tools[] = $tool['instance']);
    }

    /**
     * Register wrapper tools for progressive discovery mode.
     */
    protected function registerWrapperTools(): void
    {
        $this->tools[] = DiscoverRepositoriesTool::class;
        $this->tools[] = GetRepositoryOperationsTool::class;
        $this->tools[] = GetOperationDetailsTool::class;
        $this->tools[] = ExecuteOperationTool::class;
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected function discoverResources(): array
    {
        $resources = [];

        $excludedResources = config('restify.mcp.resources.exclude', []);
        $resourceDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Resources');
        foreach ($resourceDir as $resourceFile) {
            if ($resourceFile->isFile() && $resourceFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Resources\\'.$resourceFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedResources,
                    true) && $fqdn !== ApplicationInfo::class) {
                    $resources[] = $fqdn;
                }
            }
        }

        // Auto-discover resources from app/Restify/Mcp/Resources
        $appResourcesPath = app_path('Restify/Mcp/Resources');
        if (is_dir($appResourcesPath)) {
            $appResourceDir = new \DirectoryIterator($appResourcesPath);
            foreach ($appResourceDir as $resourceFile) {
                if ($resourceFile->isFile() && $resourceFile->getExtension() === 'php') {
                    $fqdn = 'App\\Restify\\Mcp\\Resources\\'.$resourceFile->getBasename('.php');
                    if (class_exists($fqdn) && ! in_array($fqdn, $excludedResources, true)) {
                        $resources[] = $fqdn;
                    }
                }
            }
        }

        $extraResources = config('restify.mcp.resources.include', []);
        foreach ($extraResources as $resourceClass) {
            if (class_exists($resourceClass)) {
                $resources[] = $resourceClass;
            }
        }

        return $resources;
    }

    /**
     * @return array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected function discoverPrompts(): array
    {
        $prompts = [];

        $excludedPrompts = config('restify.mcp.prompts.exclude', []);
        if (is_dir(__DIR__.DIRECTORY_SEPARATOR.'Prompts')) {
            $promptDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Prompts');
            foreach ($promptDir as $promptFile) {
                if ($promptFile->isFile() && $promptFile->getExtension() === 'php') {
                    $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Prompts\\'.$promptFile->getBasename('.php');
                    if (class_exists($fqdn) && ! in_array($fqdn, $excludedPrompts, true)) {
                        $prompts[] = $fqdn;
                    }
                }
            }
        }

        $extraPrompts = config('restify.mcp.prompts.include', []);
        foreach ($extraPrompts as $promptClass) {
            if (class_exists($promptClass)) {
                $prompts[] = $promptClass;
            }
        }

        return $prompts;
    }

    /**
     * Override this method in your app server to implement permission checks.
     */
    public function canUseTool(string|object $tool): bool
    {
        return true;
    }

    /**
     * Get all available tools with metadata (unfiltered by permissions).
     * Useful for token creation UI where you want to show all possible tools.
     *
     * IMPORTANT: This returns ALL discovered tools regardless of mode:
     * - In "direct" mode: All repository operations, actions, getters, custom tools
     * - In "wrapper" mode: The 4 wrapper tools + all repository operations, actions, getters, custom tools
     *
     * Even though only 4 wrapper tools are registered with the MCP server in wrapper mode,
     * all individual operations are still discovered and available for permission checks.
     * This allows you to create tokens with fine-grained permissions for specific operations.
     */
    public function getAllAvailableTools(): \Illuminate\Support\Collection
    {
        return McpTools::all()->map(fn (array $tool): array => [
            'name' => $tool['name'],
            'title' => $tool['title'],
            'description' => $tool['description'],
            'category' => $tool['category'],
            'type' => $tool['type'],
        ]);
    }

    /**
     * Get tools that the current user/token is authorized to use.
     */
    public function getAuthorizedTools(): \Illuminate\Support\Collection
    {
        return McpTools::authorized()->map(fn (array $tool): array => [
            'name' => $tool['name'],
            'title' => $tool['title'],
            'description' => $tool['description'],
            'category' => $tool['category'],
            'type' => $tool['type'],
        ]);
    }

}
