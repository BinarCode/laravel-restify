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
 * ALL individual operations are discovered and filtered by your `canUseTool()` method.
 *
 * **Discovery Phase** (AI explores available operations):
 * 1. AI calls `discover-repositories` → Shows only repositories with ≥1 accessible operation
 * 2. AI calls `get-repository-operations(repository="posts")` → Lists only operations user can access
 *    - If token lacks "posts-store" permission, store won't appear in the list
 *    - Actions/getters are also filtered by permission
 * 3. AI calls `get-operation-details(repository="posts", operation="store")` → Checks `canUseTool("posts-store")`
 *    - If no permission, throws AuthorizationException
 *
 * **Execution Phase** (AI executes an operation):
 * 4. AI calls `execute-operation(repository="posts", operation="store", parameters={...})`
 * 5. Wrapper looks up "posts-store" tool in McpTools
 * 6. Calls `canUseTool("posts-store")` before execution
 * 7. If yes → executes, if no → throws AuthorizationException
 *
 * **Key Point**: Your `canUseTool()` method is called during BOTH discovery and execution,
 * ensuring users only see and can execute operations they have permission for.
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

use Binaryk\LaravelRestify\MCP\Collections\ToolsCollection;
use Binaryk\LaravelRestify\MCP\Enums\ToolsCategoryEnum;
use Binaryk\LaravelRestify\MCP\Resources\ApplicationInfo;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\DiscoverRepositoriesTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\ExecuteOperationTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetOperationDetailsTool;
use Binaryk\LaravelRestify\MCP\Tools\Wrapper\GetRepositoryOperationsTool;
use Illuminate\Support\Collection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

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
     * @var array<int, class-string<Tool>>
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
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];

    protected function boot(): void
    {
        // Set this server instance in McpTools facade
        // Tools will be auto-discovered on first access via McpTools::all()
        McpTools::setServer($this);

        // Register tools with the server
        $this->discoverTools()->each(fn (string $tool): string => $this->tools[] = $tool);
        $this->registerRepositoryTools();
        collect($this->discoverResources())->each(fn (string $resource): string => $this->resources[] = $resource);
        collect($this->discoverPrompts())->each(fn (string $prompt): string => $this->prompts[] = $prompt);
    }

    /**
     * @return array<int, class-string<Tool>>
     */
    protected function discoverTools(): ToolsCollection
    {
        return McpTools::category(ToolsCategoryEnum::CUSTOM_TOOLS->value)
            ->filter(fn (array $tool): bool => $this->canUseTool($tool['instance']))
            ->pluck('class');
    }

    protected function registerRepositoryTools(): void
    {
        $mode = request()->get('mode', config('restify.mcp.mode'));

        if ($mode === 'wrapper') {
            $this->registerWrapperTools();

            return;
        }

        McpTools::all()
            ->whereIn('category', [
                ToolsCategoryEnum::CRUD_OPERATIONS->value,
                ToolsCategoryEnum::ACTIONS->value,
                ToolsCategoryEnum::GETTERS->value,
                ToolsCategoryEnum::PROFILE->value,
            ])
            ->filter(fn (array $tool): bool => $this->canUseTool($tool['instance']))
            ->each(fn (array $tool) => $this->tools[] = $tool['instance']);
    }

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
     * @return array<int, class-string<Prompt>>
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
    public function getAllAvailableTools(): Collection
    {
        return McpTools::all()->toUi();
    }

    /**
     * Get tools that the current user/token is authorized to use.
     */
    public function getAuthorizedTools(): Collection
    {
        return McpTools::authorized()->toUi();
    }
}
