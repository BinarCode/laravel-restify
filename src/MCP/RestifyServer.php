<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Getters\Getter;
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
        $tools = [];

        $excludedTools = config('restify.mcp.tools.exclude', []);
        $toolDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Tools');

        foreach ($toolDir as $toolFile) {
            if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Tools\\'.$toolFile->getBasename('.php');
                if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                    $tools[] = $fqdn;
                }
            }
        }

        // Auto-discover tools from app/Restify/Mcp/Tools
        $appToolsPath = app_path('Restify/Mcp/Tools');
        if (is_dir($appToolsPath)) {
            $appToolDir = new \DirectoryIterator($appToolsPath);
            foreach ($appToolDir as $toolFile) {
                if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
                    $fqdn = 'App\\Restify\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
                    if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                        $tools[] = $fqdn;
                    }
                }
            }
        }

        $extraTools = config('restify.mcp.tools.include', []);
        foreach ($extraTools as $toolClass) {
            if (class_exists($toolClass)) {
                $tools[] = $toolClass;
            }
        }

        return $tools;
    }

    protected function discoverRepositoryTools(): void
    {
        collect(Restify::$repositories)
            ->filter(function (string $repository) {
                return in_array(HasMcpTools::class, class_uses_recursive($repository));
            })
            ->each(function (string $repository) {
                $repositoryInstance = app($repository);

                // if it's for User repository, add the ProfileTool
                if ($repositoryInstance::uriKey() === 'users') {
                    $this->tools[] = new ProfileTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsIndex') && $repositoryInstance->mcpAllowsIndex()) {
                    $this->tools[] = new IndexTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsShow') && $repositoryInstance->mcpAllowsShow()) {
                    $this->tools[] = new ShowTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsStore') && $repositoryInstance->mcpAllowsStore()) {
                    $this->tools[] = new StoreTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsUpdate') && $repositoryInstance->mcpAllowsUpdate()) {
                    $this->tools[] = new UpdateTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsDelete') && $repositoryInstance->mcpAllowsDelete()) {
                    $this->tools[] = new DeleteTool($repository);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsActions') && $repositoryInstance->mcpAllowsActions()) {
                    $this->discoverActionsForRepository($repository, $repositoryInstance);
                }

                if (method_exists($repositoryInstance, 'mcpAllowsGetters') && $repositoryInstance->mcpAllowsGetters()) {
                    $this->discoverGettersForRepository($repository, $repositoryInstance);
                }
            });
    }

    protected function discoverActionsForRepository(string $repositoryClass, Repository $repositoryInstance): void
    {
        $actionRequest = app(McpActionRequest::class);

        $repositoryInstance->resolveActions($actionRequest)
            ->filter(fn ($action) => $action instanceof Action)
            ->filter(fn (Action $action) => $action->isShownOnMcp($actionRequest, $repositoryInstance))
            ->filter(fn (Action $action) => $action->authorizedToSee($actionRequest))
            ->unique(fn (Action $action) => $action->uriKey()) // Avoid duplicates
            ->each(fn (Action $action) => $this->tools[] = new ActionTool($repositoryClass, $action));
    }

    protected function discoverGettersForRepository(string $repositoryClass, Repository $repositoryInstance): void
    {
        $getterRequest = app(McpGetterRequest::class);

        $repositoryInstance->resolveGetters($getterRequest)
            ->filter(fn ($getter) => $getter instanceof Getter)
            ->filter(fn (Getter $getter) => $getter->isShownOnMcp($getterRequest, $repositoryInstance))
            ->filter(fn (Getter $getter) => $getter->authorizedToSee($getterRequest))
            ->unique(fn (Getter $getter) => $getter->uriKey()) // Avoid duplicates
            ->each(fn (Getter $getter) => $this->tools[] = new GetterTool($repositoryClass, $getter));
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
}
